<?php

namespace Http\Client\Socket\Tests;

use Http\Client\Socket\Client as SocketHttpClient;
use Http\Client\Tests\HttpFeatureTest;
use Psr\Http\Client\ClientInterface;

class SocketClientFeatureTest extends HttpFeatureTest
{
    protected function createClient(): ClientInterface
    {
        return new SocketHttpClient();
    }
}
