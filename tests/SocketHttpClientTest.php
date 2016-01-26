<?php

namespace Http\Client\Socket\Tests;

use Http\Client\Common\HttpMethodsClient;
use Http\Client\Socket\Client as SocketHttpClient;
use Http\Message\MessageFactory\GuzzleMessageFactory;

class SocketHttpClientTest extends BaseTestCase
{
    public function createClient($options = [])
    {
        $messageFactory = new GuzzleMessageFactory();

        return new HttpMethodsClient(new SocketHttpClient($messageFactory, $options), $messageFactory);
    }

    public function testTcpSocketDomain()
    {
        $this->startServer('tcp-server');
        $client = $this->createClient(['remote_socket' => '127.0.0.1:19999']);
        $response = $client->get('/', []);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @expectedException \Http\Client\Exception\NetworkException
     */
    public function testNoRemote()
    {
        $client = $this->createClient();
        $client->get('/', []);
    }

    public function testRemoteInUri()
    {
        $this->startServer('tcp-server');
        $client = $this->createClient();
        $response = $client->get('http://127.0.0.1:19999/', []);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRemoteInHostHeader()
    {
        $this->startServer('tcp-server');
        $client = $this->createClient();
        $response = $client->get('/', ['Host' => '127.0.0.1:19999']);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @expectedException \Http\Client\Exception\NetworkException
     */
    public function testBrokenSocket()
    {
        $this->startServer('tcp-bugous-server');
        $client = $this->createClient(['remote_socket' => '127.0.0.1:19999']);
        $client->get('/', []);
    }

    public function testSslRemoteInUri()
    {
        $this->startServer('tcp-ssl-server');
        $client = $this->createClient([
            'stream_context_options' => [
                'ssl' => [
                    'peer_name' => 'socket-adapter',
                    'cafile'    => __DIR__.'/server/ssl/ca.pem',
                ],
            ],
        ]);
        $response = $client->get('https://127.0.0.1:19999/', []);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testUnixSocketDomain()
    {
        $this->startServer('unix-domain-server');

        $client = $this->createClient([
            'remote_socket' => 'unix://'.__DIR__.'/server/server.sock',
        ]);
        $response = $client->get('/', []);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @expectedException \Http\Client\Exception\NetworkException
     */
    public function testNetworkExceptionOnConnectError()
    {
        $client = $this->createClient(['remote_socket' => '127.0.0.1:19999']);
        $client->get('/', []);
    }

    public function testSslConnection()
    {
        $this->startServer('tcp-ssl-server');

        $client = $this->createClient([
            'remote_socket'          => '127.0.0.1:19999',
            'ssl'                    => true,
            'stream_context_options' => [
                'ssl' => [
                    'peer_name' => 'socket-adapter',
                    'cafile'    => __DIR__.'/server/ssl/ca.pem',
                ],
            ],
        ]);
        $response = $client->get('/', []);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testSslConnectionWithClientCertificate()
    {
        if (version_compare(PHP_VERSION, '5.6', '<')) {
            $this->markTestSkipped('Test can only run on php 5.6 and superior (for capturing peer certificate)');
        }

        $this->startServer('tcp-ssl-server-client');

        $client = $this->createClient([
            'remote_socket'          => '127.0.0.1:19999',
            'ssl'                    => true,
            'stream_context_options' => [
                'ssl' => [
                    'peer_name'  => 'socket-adapter',
                    'cafile'     => __DIR__.'/server/ssl/ca.pem',
                    'local_cert' => __DIR__.'/server/ssl/client-and-key.pem',
                ],
            ],
        ]);
        $response = $client->get('/', []);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testInvalidSslConnectionWithClientCertificate()
    {
        if (version_compare(PHP_VERSION, '5.6', '<')) {
            $this->markTestSkipped('Test can only run on php 5.6 and superior (for capturing peer certificate)');
        }

        $this->startServer('tcp-ssl-server-client');

        $client = $this->createClient([
            'remote_socket'          => '127.0.0.1:19999',
            'ssl'                    => true,
            'stream_context_options' => [
                'ssl' => [
                    'peer_name'  => 'socket-adapter',
                    'cafile'     => __DIR__.'/server/ssl/ca.pem',
                ],
            ],
        ]);
        $response = $client->get('/', []);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @expectedException \Http\Client\Exception\NetworkException
     */
    public function testNetworkExceptionOnSslError()
    {
        $this->startServer('tcp-server');

        $client = $this->createClient(['remote_socket' => '127.0.0.1:19999', 'ssl' => true]);
        $client->get('/', []);
    }

    /**
     * @expectedException \Http\Client\Exception\NetworkException
     */
    public function testNetworkExceptionOnTimeout()
    {
        $client = $this->createClient(['timeout' => 10]);
        $client->get('http://php.net', []);
    }
}
