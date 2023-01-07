<?php

namespace Tarekdj\DockerClient;

use Http\Message\Encoding\ChunkStream;
use Http\Message\Encoding\DechunkStream;
use Http\Message\Encoding\DecompressStream;
use Http\Message\Encoding\GzipDecodeStream;
use Nyholm\Psr7\Uri;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tarekdj\DockerClient\Exception\ConnectionException;
use Tarekdj\DockerClient\Exception\InvalidRequestException;
use Tarekdj\DockerClient\Exception\SSLConnectionException;
use Tarekdj\DockerClient\Exception\TimeoutException;

/**
 * Socket Http Client.
 *
 * Use stream and socket capabilities of the core of PHP to send HTTP requests
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
class Client implements ClientInterface
{
    use RequestWriter;
    use ResponseReader;

    /**
     * @var array{remote_socket: string|null, timeout: int, stream_context: resource, stream_context_options: array<string, mixed>, stream_context_param: array<string, mixed>, ssl: ?boolean, write_buffer_size: int, ssl_method: int}
     */
    private array $config;

    /**
     * Constructor.
     *
     * @param array{remote_socket?: string|null, timeout?: int, stream_context?: resource, stream_context_options?: array<string, mixed>, stream_context_param?: array<string, mixed>, ssl?: ?boolean, write_buffer_size?: int, ssl_method?: int}|ResponseFactoryInterface $config1
     *
     * string|null          remote_socket          Remote entrypoint (can be a tcp or unix domain address)
     * int                  timeout                Timeout before canceling request
     * stream               resource               The initialized stream context, if not set the context is created from the options and param.
     * array<string, mixed> stream_context_options Context options as defined in the PHP documentation
     * array<string, mixed> stream_context_param   Context params as defined in the PHP documentation
     * boolean              ssl                    Use ssl, default to scheme from request, false if not present
     * int                  write_buffer_size      Buffer when writing the request body, defaults to 8192
     * int                  ssl_method             Crypto method for ssl/tls, see PHP doc, defaults to STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT
     */
    public function __construct(array $config1 = [])
    {
        $this->config = $this->configure($config1);
    }

    /**
     * {@inheritdoc}
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $remote = $this->config['remote_socket'];
        $useSsl = $this->config['ssl'];

        if (!$request->hasHeader('Connection')) {
            $request = $request->withHeader('Connection', 'close');
        }

        if (null === $remote) {
            $remote = $this->determineRemoteFromRequest($request);
        }

        if (null === $useSsl) {
            $useSsl = ('https' === $request->getUri()->getScheme());
        }

        $request = $this->addHost($request);
        $request = $this->addContentLength($request);
        $request = $this->addDecoder($request);

        $socket = $this->createSocket($request, $remote, $useSsl);

        try {
            $this->writeRequest($socket, $request, $this->config['write_buffer_size']);
            $response = $this->readResponse($request, $socket);
        } catch (\Throwable $e) {
            $this->closeSocket($socket);

            throw $e;
        }

        return $this->decodeResponse($response);
    }

    public function getConfig()
    {
        return $this->config;
    }

    protected function decodeResponse(ResponseInterface $response): ResponseInterface
    {
        $response = $this->decodeOnEncodingHeader('Transfer-Encoding', $response);

        return $this->decodeOnEncodingHeader('Content-Encoding', $response);
    }

    private function decodeOnEncodingHeader(string $headerName, ResponseInterface $response): ResponseInterface
    {
        if ($response->hasHeader($headerName)) {
            $encodings = $response->getHeader($headerName);
            $newEncodings = [];

            while ($encoding = array_pop($encodings)) {
                $stream = $this->decorateStream($encoding, $response->getBody());

                if (false === $stream) {
                    array_unshift($newEncodings, $encoding);

                    continue;
                }

                $response = $response->withBody($stream);
            }

            if ($newEncodings !== []) {
                $response = $response->withHeader($headerName, $newEncodings);
            } else {
                $response = $response->withoutHeader($headerName);
            }
        }

        return $response;
    }

    private function decorateStream(string $encoding, StreamInterface $stream)
    {
        if ('chunked' === strtolower($encoding)) {
            return new DechunkStream($stream);
        }

        if ('deflate' === strtolower($encoding)) {
            return new DecompressStream($stream);
        }

        if ('gzip' === strtolower($encoding)) {
            return new GzipDecodeStream($stream);
        }

        return false;
    }

    /**
     * @todo: implement correctly.
     */
    protected function addHost(RequestInterface $request): RequestInterface
    {
        $host = new Uri('http://localhost');
        $uri = $request->getUri()
            ->withHost($host->getHost())
            ->withScheme($host->getScheme())
            ->withPort($host->getPort());

        return $request->withUri($uri);
    }

    protected function addContentLength(RequestInterface $request): RequestInterface
    {
        if (!$request->hasHeader('Content-Length')) {
            $stream = $request->getBody();

            // Cannot determine the size so we use a chunk stream
            if (null === $stream->getSize()) {
                $stream = new ChunkStream($stream);
                $request = $request->withBody($stream);
                $request = $request->withAddedHeader('Transfer-Encoding', 'chunked');
            } else {
                $request = $request->withHeader('Content-Length', (string) $stream->getSize());
            }
        }

        return $request;
    }

    protected function addDecoder(RequestInterface $request): RequestInterface
    {
        $encodings = extension_loaded('zlib') ? ['gzip', 'deflate'] : ['identity'];
        $request = $request->withHeader('Accept-Encoding', $encodings);

        $encodings[] = 'chunked';

        return $request->withHeader('TE', $encodings);
    }

    /**
     * Create the socket to write request and read response on it.
     *
     * @param RequestInterface $request Request for
     * @param string           $remote  Entrypoint for the connection
     * @param bool             $useSsl  Whether to use ssl or not
     *
     * @return resource Socket resource
     *
     * @throws ConnectionException|SSLConnectionException When the connection fail
     */
    protected function createSocket(RequestInterface $request, string $remote, bool $useSsl)
    {
        $errNo = null;
        $errMsg = null;
        $socket = @stream_socket_client($remote, $errNo, $errMsg, floor($this->config['timeout'] / 1000), STREAM_CLIENT_CONNECT, $this->config['stream_context']);

        if (false === $socket) {
            if (110 === $errNo) {
                throw new TimeoutException($errMsg, $request);
            }

            throw new ConnectionException($errMsg, $request);
        }

        stream_set_timeout($socket, (int) floor($this->config['timeout'] / 1000), $this->config['timeout'] % 1000);

        if ($useSsl && false === @stream_socket_enable_crypto($socket, true, $this->config['ssl_method'])) {
            throw new SSLConnectionException(sprintf('Cannot enable tls: %s', error_get_last()['message'] ?? 'no error reported'), $request);
        }

        return $socket;
    }

    /**
     * Close the socket, used when having an error.
     *
     * @param resource $socket
     *
     * @return void
     */
    protected function closeSocket($socket)
    {
        fclose($socket);
    }

    /**
     * Return configuration for the socket client.
     *
     * @param array{remote_socket?: string|null, timeout?: int, stream_context?: resource, stream_context_options?: array<string, mixed>, stream_context_param?: array<string, mixed>, ssl?: ?boolean, write_buffer_size?: int, ssl_method?: int} $config
     *
     * @return array{remote_socket: string|null, timeout: int, stream_context: resource, stream_context_options: array<string, mixed>, stream_context_param: array<string, mixed>, ssl: ?boolean, write_buffer_size: int, ssl_method: int}
     */
    protected function configure(array $config = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'remote_socket' => null,
            'timeout' => null,
            'stream_context_options' => [],
            'stream_context_param' => [],
            'ssl' => null,
            'write_buffer_size' => 8192,
            'ssl_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
        ]);
        $resolver->setDefault('stream_context', fn(Options $options) => stream_context_create($options['stream_context_options'], $options['stream_context_param']));
        $resolver->setDefault('timeout', ((int) ini_get('default_socket_timeout')) * 1000);

        $resolver->setAllowedTypes('stream_context_options', 'array');
        $resolver->setAllowedTypes('stream_context_param', 'array');
        $resolver->setAllowedTypes('stream_context', 'resource');
        $resolver->setAllowedTypes('ssl', ['bool', 'null']);

        return $resolver->resolve($config);
    }

    /**
     * Return remote socket from the request.
     *
     *
     * @throws InvalidRequestException When no remote can be determined from the request
     */
    private function determineRemoteFromRequest(RequestInterface $request): string
    {
        if (!$request->hasHeader('Host') && '' === $request->getUri()->getHost()) {
            throw new InvalidRequestException('Remote is not defined and we cannot determine a connection endpoint for this request (no Host header)', $request);
        }

        $host = $request->getUri()->getHost();
        $port = $request->getUri()->getPort() ?: ('https' === $request->getUri()->getScheme() ? 443 : 80);
        $endpoint = sprintf('%s:%s', $host, $port);

        // If use the host header if present for the endpoint
        if (empty($host) && $request->hasHeader('Host')) {
            $endpoint = $request->getHeaderLine('Host');
        }

        return sprintf('tcp://%s', $endpoint);
    }
}
