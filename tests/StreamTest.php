<?php

namespace Http\Client\Socket\Tests;

use Http\Client\Socket\Exception\StreamException;
use Http\Client\Socket\Exception\TimeoutException;
use Http\Client\Socket\Stream;
use Nyholm\Psr7\Request;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    public function createSocket($body, $useSize = true): Stream
    {
        $socket = fopen('php://memory', 'rwb');
        fwrite($socket, $body);
        fseek($socket, 0);

        return new Stream(new Request('GET', '/'), $socket, $useSize ? strlen($body) : null);
    }

    public function testToString(): void
    {
        $stream = $this->createSocket('Body');

        $this->assertEquals('Body', $stream->__toString());
        $stream->close();
    }

    public function testSubsequentCallIsEmpty(): void
    {
        $stream = $this->createSocket('Body');

        $this->assertEquals('Body', $stream->getContents());
        $this->assertEmpty($stream->getContents());
        $stream->close();
    }

    public function testDetach(): void
    {
        $stream = $this->createSocket('Body');
        $socket = $stream->detach();

        $this->assertIsResource($socket);
        $this->assertNull($stream->detach());
    }

    public function testTell(): void
    {
        $stream = $this->createSocket('Body');

        $this->assertEquals(0, $stream->tell());
        $this->assertEquals('Body', $stream->getContents());
        $this->assertEquals(4, $stream->tell());
    }

    public function testEof(): void
    {
        $socket = fopen('php://memory', 'rwb+');
        fwrite($socket, 'Body');
        fseek($socket, 0);
        $stream = new Stream(new Request('GET', '/'), $socket);

        $this->assertEquals('Body', $stream->getContents());
        fwrite($socket, "\0");
        $this->assertTrue($stream->eof());
        $stream->close();
    }

    public function testNotSeekable(): void
    {
        $stream = $this->createSocket('Body');

        $this->assertFalse($stream->isSeekable());

        $this->expectException(StreamException::class);
        $stream->seek(0);
    }

    public function testNoRewind(): void
    {
        $stream = $this->createSocket('Body');

        $this->expectException(StreamException::class);
        $stream->rewind();
    }

    public function testNotWritable(): void
    {
        $stream = $this->createSocket('Body');

        $this->assertFalse($stream->isWritable());

        $this->expectException(StreamException::class);
        $stream->write('Test');
    }

    public function testIsReadable(): void
    {
        $stream = $this->createSocket('Body');

        $this->assertTrue($stream->isReadable());
    }

    public function testTimeout(): void
    {
        $socket = fsockopen('php.net', 80);
        stream_set_timeout($socket, 0, 100);

        $stream = new Stream(new Request('GET', '/'), $socket, 50);
        $this->expectException(TimeoutException::class);
        $stream->getContents();
    }

    public function testMetadatas(): void
    {
        $stream = $this->createSocket('Body', false);

        $this->assertEquals('PHP', $stream->getMetadata('wrapper_type'));
        $this->assertEquals('MEMORY', $stream->getMetadata('stream_type'));
        $this->assertEquals('php://memory', $stream->getMetadata('uri'));
        $this->assertFalse($stream->getMetadata('timed_out'));
        $this->assertFalse($stream->getMetadata('eof'));
        $this->assertTrue($stream->getMetadata('blocked'));
    }

    public function testClose(): void
    {
        $socket = fopen('php://memory', 'rwb+');
        fwrite($socket, 'Body');
        fseek($socket, 0);

        $stream = new Stream(new Request('GET', '/'), $socket);
        $stream->close();

        $this->assertFalse(is_resource($socket)); // phpstorm thinks we could assertNotIsResource, but closed resources seem to behave differently
    }

    public function testRead(): void
    {
        $stream = $this->createSocket('Body');

        $this->assertEquals('Bod', $stream->read(3));
        $this->assertEquals('y', $stream->read(3));

        $stream->close();
    }
}
