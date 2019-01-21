<?php

namespace Http\Client\Socket\Tests;

use Http\Client\Tests\HttpClientTest;
use Http\Client\Socket\Client as SocketHttpClient;

class SocketHttpAdapterTest extends HttpClientTest
{
    /**
     * {@inheritdoc}
     */
    protected function createHttpAdapter()
    {
        return new SocketHttpClient();
    }
}
