<?php

namespace Http\Client\Socket\Tests;

use Http\Client\Socket\Client as SocketHttpClient;
use Http\Client\Tests\HttpFeatureTest;

class SocketClientFeatureTest extends HttpFeatureTest
{
    protected function createClient()
    {
        return new SocketHttpClient();
    }
}
