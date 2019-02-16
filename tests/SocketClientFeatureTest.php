<?php

namespace Http\Client\Socket\Tests;

use Http\Client\Tests\HttpFeatureTest;
use Http\Client\Socket\Client as SocketHttpClient;
use Psr\Http\Client\ClientInterface;

class SocketClientFeatureTest extends HttpFeatureTest
{
    protected function createClient(): ClientInterface
    {
        return new SocketHttpClient();
    }
}
