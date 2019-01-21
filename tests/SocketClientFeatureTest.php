<?php

namespace Http\Client\Socket\Tests;

use Http\Client\Tests\HttpFeatureTest;
use Http\Client\Socket\Client as SocketHttpClient;

class SocketClientFeatureTest extends HttpFeatureTest
{
    protected function createClient()
    {
        return new SocketHttpClient();
    }
}
