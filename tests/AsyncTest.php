<?php

namespace Http\Client\Socket\Tests;

use function Concurrent\all;
use Concurrent\Task;
use GuzzleHttp\Psr7\Request;
use Http\Client\Socket\Client;
use PHPUnit\Framework\TestCase;

class AsyncTest extends TestCase
{
    public function testAsync()
    {
        if (PHP_VERSION_ID < 70300 || !extension_loaded('async')) {
            $this->markTestSkipped('Test need async extension');
        }

        $client = new Client();
        $request = new Request('GET', 'https://httpbin.org/delay/1');

        $timeStart = microtime(true);
        $task1 = Task::async(function () use ($request, $client) {
            return $client->sendRequest($request);
        });
        $task2 = Task::async(function () use ($request, $client) {
            return $client->sendRequest($request);
        });

        [$response1, $response2] = Task::await(all([$task1, $task2]));
        $timeTaken = microtime(true) - $timeStart;

        self::assertLessThan(2, $timeTaken);
        self::assertEquals(200, $response1->getStatusCode());
        self::assertEquals(200, $response2->getStatusCode());
    }
}
