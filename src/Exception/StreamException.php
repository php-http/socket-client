<?php

namespace Http\Client\Socket\Exception;

use Http\Client\Exception;
use Psr\Http\Message\RequestInterface;

class StreamException extends \RuntimeException implements Exception
{
    /**
     * The request object
     * @var RequestInterface
     */
    private $request;

    /**
     * Accepts an optional request object as second param
     *
     * @param string $message
     * @param RequestInterface $request
     * @param long $code
     * @param Exception $previous
     */
    public function __construct($message = null, RequestInterface $request = null, $code = null, $previous = null)
    {
        $this->request=$request;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return \Psr\Http\Message\RequestInterface|NULL
     */
    final public function getRequest()
    {
        return $this->request;
    }
}

