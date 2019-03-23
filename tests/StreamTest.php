<?php

namespace Http\Client\Socket\Tests;

use Http\Client\Socket\Stream;
use Nyholm\Psr7\Request;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    public function createSocket($body, $useSize = true)
    {
        $socket = fopen('php://memory', 'rw');
        fwrite($socket, $body);
        fseek($socket, 0);

        return new Stream(new Request('GET', '/'), $socket, $useSize ? strlen($body) : null);
    }

    public function testToString()
    {
        $stream = $this->createSocket('Body');

        $this->assertEquals('Body', $stream->__toString());
        $stream->close();
    }

    public function testSubsequentCallIsEmpty()
    {
        $stream = $this->createSocket('Body');

        $this->assertEquals('Body', $stream->getContents());
        $this->assertEmpty($stream->getContents());
        $stream->close();
    }

    public function testDetach()
    {
        $stream = $this->createSocket('Body');
        $socket = $stream->detach();

        $this->assertTrue(is_resource($socket));
        $this->assertNull($stream->detach());
    }

    public function testTell()
    {
        $stream = $this->createSocket('Body');

        $this->assertEquals(0, $stream->tell());
        $this->assertEquals('Body', $stream->getContents());
        $this->assertEquals(4, $stream->tell());
    }

    public function testEof()
    {
        $socket = fopen('php://memory', 'rw+');
        fwrite($socket, 'Body');
        fseek($socket, 0);
        $stream = new Stream(new Request('GET', '/'), $socket);

        $this->assertEquals('Body', $stream->getContents());
        fwrite($socket, "\0");
        $this->assertTrue($stream->eof());
        $stream->close();
    }

    public function testNotSeekable()
    {
        $stream = $this->createSocket('Body');

        $this->assertFalse($stream->isSeekable());

        try {
            $stream->seek(0);
        } catch (\Exception $e) {
            $this->assertInstanceOf('Http\Client\Socket\Exception\StreamException', $e);
        }
    }

    public function testNoRewing()
    {
        $stream = $this->createSocket('Body');

        try {
            $stream->rewind();
        } catch (\Exception $e) {
            $this->assertInstanceOf('Http\Client\Socket\Exception\StreamException', $e);
        }
    }

    public function testNotWritable()
    {
        $stream = $this->createSocket('Body');

        $this->assertFalse($stream->isWritable());

        try {
            $stream->write('Test');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Http\Client\Socket\Exception\StreamException', $e);
        }
    }

    public function testIsReadable()
    {
        $stream = $this->createSocket('Body');

        $this->assertTrue($stream->isReadable());
    }

    /**
     * @expectedException \Http\Client\Socket\Exception\TimeoutException
     */
    public function testTimeout()
    {
        $socket = fsockopen('php.net', 80);
        socket_set_timeout($socket, 0, 100);

        $stream = new Stream(new Request('GET', '/'), $socket, 50);
        $stream->getContents();
    }

    public function testMetadatas()
    {
        $stream = $this->createSocket('Body', false);

        $this->assertEquals('PHP', $stream->getMetadata('wrapper_type'));
        $this->assertEquals('MEMORY', $stream->getMetadata('stream_type'));
        $this->assertEquals('php://memory', $stream->getMetadata('uri'));
        $this->assertFalse($stream->getMetadata('timed_out'));
        $this->assertFalse($stream->getMetadata('eof'));
        $this->assertTrue($stream->getMetadata('blocked'));
    }

    public function testClose()
    {
        $socket = fopen('php://memory', 'rw+');
        fwrite($socket, 'Body');
        fseek($socket, 0);

        $stream = new Stream(new Request('GET', '/'), $socket);
        $stream->close();

        $this->assertFalse(is_resource($socket));
    }

    public function testRead()
    {
        $stream = $this->createSocket('Body');

        $this->assertEquals('Bod', $stream->read(3));
        $this->assertEquals('y', $stream->read(3));

        $stream->close();
    }
}
