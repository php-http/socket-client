<?php

namespace Http\Client\Socket\Tests;

use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase
{
    private $servers = [];

    public function startServer($name)
    {
        $filename = __DIR__.'/server/'.$name.'.php';
        $pipes = [];

        if (!Semaphore::acquire()) {
            $this->fail('Could not connect to server');
        }

        $this->servers[$name] = proc_open('php '.$filename, [], $pipes);
        sleep(1);
    }

    public function stopServer($name)
    {
        if (isset($this->servers[$name])) {
            proc_terminate($this->servers[$name], SIGKILL);
        }
    }

    public function tearDown(): void
    {
        foreach (array_keys($this->servers) as $name) {
            $this->stopServer($name);
        }

        Semaphore::release();
    }
}
