<?php

namespace Http\Socket\Tests;

use Http\Adapter\Tests\HttpAdapterTest;
use Http\Discovery\MessageFactory\GuzzleFactory;
use Http\Socket\SocketHttpClient;

class SocketHttpAdapterTest extends HttpAdapterTest
{
    /**
     * {@inheritdoc}
     */
    protected function createHttpAdapter()
    {
        return new SocketHttpClient(new GuzzleFactory());
    }
}
