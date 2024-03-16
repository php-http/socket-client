<?php

namespace Http\Client\Socket\Tests;

use Http\Client\Common\HttpMethodsClient;
use Http\Client\Socket\Client as SocketHttpClient;
use Http\Client\Socket\Exception\NetworkException;
use Http\Client\Socket\Exception\TimeoutException;
use Nyholm\Psr7\Factory\Psr17Factory;

class SocketHttpClientTest extends BaseTestCase
{
    public function createClient($options = [])
    {
        return new HttpMethodsClient(new SocketHttpClient($options), new Psr17Factory());
    }

    public function testTcpSocketDomain()
    {
        $this->startServer('tcp-server');
        $client = $this->createClient(['remote_socket' => '127.0.0.1:19999']);
        $response = $client->get('/', []);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testNoRemote(): void
    {
        $client = $this->createClient();
        $this->expectException(NetworkException::class);
        $client->get('/', []);
    }

    public function testRemoteInUri(): void
    {
        $this->startServer('tcp-server');
        $client = $this->createClient();
        $response = $client->get('http://127.0.0.1:19999/', []);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRemoteInHostHeader(): void
    {
        $this->startServer('tcp-server');
        $client = $this->createClient();
        $response = $client->get('/', ['Host' => '127.0.0.1:19999']);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBrokenSocket(): void
    {
        $this->startServer('tcp-bugous-server');
        $client = $this->createClient(['remote_socket' => '127.0.0.1:19999']);
        $this->expectException(NetworkException::class);
        $client->get('/', []);
    }

    public function testSslRemoteInUri(): void
    {
        $this->startServer('tcp-ssl-server');
        $client = $this->createClient([
            'remote_socket' => 'tcp://127.0.0.1:19999',
            'ssl' => true,
            'stream_context_options' => [
                'ssl' => [
                    'peer_name' => 'socket-adapter',
                    'cafile' => __DIR__.'/server/ssl/ca.pem',
                ],
            ],
        ]);
        $response = $client->get('/', []);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUnixSocketDomain(): void
    {
        $this->startServer('unix-domain-server');

        $client = $this->createClient([
            'remote_socket' => 'unix://'.__DIR__.'/server/server.sock',
        ]);
        $response = $client->get('/', []);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testNetworkExceptionOnConnectError(): void
    {
        $client = $this->createClient(['remote_socket' => '127.0.0.1:19999']);
        $this->expectException(NetworkException::class);
        $client->get('/', []);
    }

    public function testSslConnection()
    {
        $this->startServer('tcp-ssl-server');

        $client = $this->createClient([
            'remote_socket' => '127.0.0.1:19999',
            'ssl' => true,
            'stream_context_options' => [
                'ssl' => [
                    'peer_name' => 'socket-adapter',
                    'cafile' => __DIR__.'/server/ssl/ca.pem',
                ],
            ],
        ]);
        $response = $client->get('/', []);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testSslConnectionWithClientCertificate(): void
    {
        $this->startServer('tcp-ssl-server-client');

        $client = $this->createClient([
            'remote_socket' => '127.0.0.1:19999',
            'ssl' => true,
            'stream_context_options' => [
                'ssl' => [
                    'peer_name' => 'socket-adapter',
                    'cafile' => __DIR__.'/server/ssl/ca.pem',
                    'local_cert' => __DIR__.'/server/ssl/client-and-key.pem',
                ],
            ],
        ]);
        $response = $client->get('/', []);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testInvalidSslConnectionWithClientCertificate(): void
    {
        $this->startServer('tcp-ssl-server-client');

        $client = $this->createClient([
            'remote_socket' => '127.0.0.1:19999',
            'ssl' => true,
            'stream_context_options' => [
                'ssl' => [
                    'peer_name' => 'socket-adapter',
                    'cafile' => __DIR__.'/server/ssl/ca.pem',
                ],
            ],
        ]);
        $response = $client->get('/', []);

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testNetworkExceptionOnSslError(): void
    {
        $this->startServer('tcp-server');

        $client = $this->createClient(['remote_socket' => '127.0.0.1:19999', 'ssl' => true]);
        $this->expectException(NetworkException::class);
        $client->get('/', []);
    }

    public function testNetworkExceptionOnTimeout(): void
    {
        $client = $this->createClient(['timeout' => 1]);
        $this->expectException(TimeoutException::class);
        $response = $client->get('https://php.net', []);
        $response->getBody()->getContents();
    }
}
