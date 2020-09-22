<?php

namespace Http\Client\Socket\Tests;

use Http\Client\Socket\Client as SocketHttpClient;
use Http\Client\Tests\HttpClientTest;
use Psr\Http\Client\ClientInterface;

class SocketHttpAdapterTest extends HttpClientTest
{
    /**
     * {@inheritdoc}
     */
    protected function createHttpAdapter(): ClientInterface
    {
        return new SocketHttpClient();
    }
}
