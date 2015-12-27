<?php

namespace Http\Socket\Tests;

use Http\Client\Tests\HttpFeatureTest;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Http\Socket\SocketHttpClient;

class SocketClientFeatureTest extends HttpFeatureTest
{
    protected function createClient()
    {
        return new SocketHttpClient(new GuzzleMessageFactory());
    }
}
