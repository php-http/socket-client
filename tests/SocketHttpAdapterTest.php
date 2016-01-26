<?php

namespace Http\Client\Socket\Tests;

use Http\Client\Socket\Client as SocketHttpClient;
use Http\Client\Tests\HttpClientTest;

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
