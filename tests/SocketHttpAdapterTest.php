<?php

namespace Http\Socket\Tests;

use Http\Client\Tests\HttpClientTest;
use Http\Discovery\MessageFactory\GuzzleFactory;
use Http\Socket\SocketHttpClient;

class SocketHttpAdapterTest extends HttpClientTest
{
    /**
     * {@inheritdoc}
     */
    protected function createHttpAdapter()
    {
        return new SocketHttpClient(new GuzzleFactory());
    }
}
