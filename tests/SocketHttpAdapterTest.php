<?php

namespace Http\Client\Socket\Tests;

use Http\Client\Tests\HttpClientTest;
use Http\Client\Socket\Client as SocketHttpClient;
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
