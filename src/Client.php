<?php

namespace Http\Client\Socket;

use Http\Client\HttpClient;
use Http\Client\Socket\Exception\ConnectionException;
use Http\Client\Socket\Exception\InvalidRequestException;
use Http\Client\Socket\Exception\SSLConnectionException;
use Http\Client\Socket\Exception\TimeoutException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Socket Http Client.
 *
 * Use stream and socket capabilities of the core of PHP to send HTTP requests
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
class Client implements HttpClient
{
    use RequestWriter;
    use ResponseReader;

    /**
     * @var array{remote_socket: string|null, timeout: int, stream_context: resource, stream_context_options: array<string, mixed>, stream_context_param: array<string, mixed>, ssl: ?boolean, write_buffer_size: int, ssl_method: int}
     */
    private $config;

    /**
     * Constructor.
     *
     * @param array{remote_socket?: string|null, timeout?: int, stream_context?: resource, stream_context_options?: array<string, mixed>, stream_context_param?: array<string, mixed>, ssl?: ?boolean, write_buffer_size?: int, ssl_method?: int}|ResponseFactoryInterface $config1
     * @param array{remote_socket?: string|null, timeout?: int, stream_context?: resource, stream_context_options?: array<string, mixed>, stream_context_param?: array<string, mixed>, ssl?: ?boolean, write_buffer_size?: int, ssl_method?: int}|null                     $config2 Mistake when refactoring the constructor from version 1 to version 2 - used as $config if set and $configOrResponseFactory is a response factory instance
     * @param array{remote_socket?: string|null, timeout?: int, stream_context?: resource, stream_context_options?: array<string, mixed>, stream_context_param?: array<string, mixed>, ssl?: ?boolean, write_buffer_size?: int, ssl_method?: int}                          $config  intended for version 1 BC, used as $config if $config2 is not set and $configOrResponseFactory is a response factory instance
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
    public function __construct($config1 = [], $config2 = null, array $config = [])
    {
        if (\is_array($config1)) {
            $this->config = $this->configure($config1);

            return;
        }

        @trigger_error('Passing a Psr\Http\Message\ResponseFactoryInterface to SocketClient is deprecated, and will be removed in 3.0, you should only pass config options.', E_USER_DEPRECATED);

        $this->config = $this->configure($config2 ?: $config);
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

        $socket = $this->createSocket($request, $remote, $useSsl);

        try {
            $this->writeRequest($socket, $request, $this->config['write_buffer_size']);
            $response = $this->readResponse($request, $socket);
        } catch (\Exception $e) {
            $this->closeSocket($socket);

            throw $e;
        }

        return $response;
    }

    /**
     * Create the socket to write request and read response on it.
     *
     * @param RequestInterface $request Request for
     * @param string           $remote  Entrypoint for the connection
     * @param bool             $useSsl  Whether to use ssl or not
     *
     * @throws ConnectionException|SSLConnectionException When the connection fail
     *
     * @return resource Socket resource
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
        $resolver->setDefault('stream_context', function (Options $options) {
            return stream_context_create($options['stream_context_options'], $options['stream_context_param']);
        });
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
     * @throws InvalidRequestException When no remote can be determined from the request
     *
     * @return string
     */
    private function determineRemoteFromRequest(RequestInterface $request)
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
