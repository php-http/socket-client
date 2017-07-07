<?php

namespace Http\Client\Socket\Tests;

use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase
{
    private $servers = [];

    public function startServer($name)
    {
        $filename = __DIR__ . '/server/' . $name . '.php';
        $pipes    = [];
        $this->servers[$name] = proc_open('php '. $filename, [], $pipes);
        usleep(300000);
    }

    public function stopServer($name)
    {
        if (isset($this->servers[$name])) {
            proc_terminate($this->servers[$name], SIGKILL);
        }
    }

    public function tearDown()
    {
        foreach (array_keys($this->servers) as $name) {
            $this->stopServer($name);
        }
    }
}
