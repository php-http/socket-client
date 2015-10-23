<?php

namespace Http\Socket;

use Http\Socket\Exception\StreamException;
use Http\Socket\Exception\TimeoutException;
use Psr\Http\Message\StreamInterface;

/**
 * Stream implementation for Socket Client
 *
 * This implementation is used to have a Stream which react better to the Socket Client behavior.
 *
 * The main advantage is you can get the response of a request even if it's not finish, the response is available
 * as soon as all headers are received, this stream will have the remaining socket used for the request / response
 * call.
 *
 * It is only readable once, if you want to read the content multiple times, you can store contents of this
 * stream into a variable or encapsulate it in a buffered stream.
 *
 * Writing and seeking is disable to avoid weird behaviors.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
class Stream implements StreamInterface
{
    /** @var resource Underlying socket */
    private $socket;

    /**
     * @var bool Is stream detached
     */
    private $isDetached = false;

    /**
     * @var int|null Size of the stream, so we know what we must read, null if not available (i.e. a chunked stream)
     */
    private $size;

    /**
     * @var int Size of the stream readed, to avoid reading more than available and have the user blocked
     */
    private $readed = 0;

    /**
     * Create the stream
     *
     * @param resource $socket
     * @param integer  $size
     */
    public function __construct($socket, $size = null)
    {
        $this->socket = $socket;
        $this->size   = $size;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return (string)$this->getContents();
    }

    /**
     * {@inheritDoc}
     */
    public function close()
    {
        fclose($this->socket);
    }

    /**
     * {@inheritDoc}
     */
    public function detach()
    {
        $this->isDetached = true;
        $socket = $this->socket;
        $this->socket = null;

        return $socket;
    }

    /**
     * {@inheritDoc}
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * {@inheritDoc}
     */
    public function tell()
    {
        return ftell($this->socket);
    }

    /**
     * {@inheritDoc}
     */
    public function eof()
    {
        return feof($this->socket);
    }

    /**
     * {@inheritDoc}
     */
    public function isSeekable()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        throw new StreamException("This stream is not seekable");
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        throw new StreamException("This stream is not seekable");
    }

    /**
     * {@inheritDoc}
     */
    public function isWritable()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function write($string)
    {
        throw new StreamException("This stream is not writable");
    }

    /**
     * {@inheritDoc}
     */
    public function isReadable()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function read($length)
    {
        if (null === $this->getSize()) {
            return fread($this->socket, $length);
        }

        if ($this->getSize() < ($this->readed + $length)) {
            throw new StreamException("Cannot read more than %s", $this->getSize() - $this->readed);
        }

        if ($this->getSize() === $this->readed) {
            return "";
        }

        // Even if we request a length a non blocking stream can return less data than asked
        $read = fread($this->socket, $length);

        if ($this->getMetadata('timed_out')) {
            throw new TimeoutException("Stream timed out while reading data");
        }

        $this->readed += strlen($read);

        return $read;
    }

    /**
     * {@inheritDoc}
     */
    public function getContents()
    {
        if (null === $this->getSize()) {
            return stream_get_contents($this->socket);
        }

        return $this->read($this->getSize() - $this->readed);
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata($key = null)
    {
        $meta = stream_get_meta_data($this->socket);

        if (null === $key) {
            return $meta;
        }

        return $meta[$key];
    }
}
