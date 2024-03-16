<?php

namespace Http\Client\Socket;

use Http\Client\Socket\Exception\BrokenPipeException;
use Http\Client\Socket\Exception\TimeoutException;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Method for reading response.
 *
 * Mainly used by SocketHttpClient
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
trait ResponseReader
{
    /**
     * Read a response from a socket.
     *
     * @param resource $socket
     *
     * @throws TimeoutException    When the socket timed out
     * @throws BrokenPipeException When the response cannot be read
     */
    protected function readResponse(RequestInterface $request, $socket): ResponseInterface
    {
        $headers = [];
        $reason = null;

        while (false !== ($line = fgets($socket))) {
            if ('' === rtrim($line)) {
                break;
            }
            $headers[] = trim($line);
        }

        $metadatas = stream_get_meta_data($socket);

        if (array_key_exists('timed_out', $metadatas) && true === $metadatas['timed_out']) {
            throw new TimeoutException('Error while reading response, stream timed out', $request, null);
        }
        $header = array_shift($headers);
        $parts = null !== $header ? explode(' ', $header, 3) : [];

        if (count($parts) <= 1) {
            throw new BrokenPipeException('Cannot read the response', $request);
        }

        $protocol = substr($parts[0], -3);
        $status = $parts[1];

        if (isset($parts[2])) {
            $reason = $parts[2];
        }

        // Set the size on the stream if it was returned in the response
        $responseHeaders = [];

        foreach ($headers as $header) {
            $headerParts = explode(':', $header, 2);

            if (!array_key_exists(trim($headerParts[0]), $responseHeaders)) {
                $responseHeaders[trim($headerParts[0])] = [];
            }

            $responseHeaders[trim($headerParts[0])][] = isset($headerParts[1])
                ? trim($headerParts[1])
                : '';
        }

        $response = new Response((int) $status, $responseHeaders, null, $protocol, $reason);
        $stream = $this->createStream($socket, $request, $response);

        return $response->withBody($stream);
    }

    /**
     * Create the stream.
     *
     * @param resource $socket
     */
    protected function createStream($socket, RequestInterface $request, ResponseInterface $response): Stream
    {
        $size = null;

        if ($response->hasHeader('Content-Length')) {
            $size = (int) $response->getHeaderLine('Content-Length');
        }
        if ($size < 0) {
            $size = null;
        }

        return new Stream($request, $socket, $size);
    }
}
