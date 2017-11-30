<?php

namespace Http\Client\Socket\Exception;

use Http\Client\Exception;
use Psr\Http\Message\RequestInterface;

class StreamException extends \RuntimeException implements Exception
{
    /**
     * The request object.
     *
     * @var RequestInterface
     */
    private $request;

    /**
     * Accepts an optional request object as 4th param.
     *
     * @param string           $message
     * @param int              $code
     * @param Exception        $previous
     * @param RequestInterface $request
     */
    public function __construct($message = null, $code = null, $previous = null, RequestInterface $request = null)
    {
        $this->request = $request;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return \Psr\Http\Message\RequestInterface|null
     */
    final public function getRequest()
    {
        return $this->request;
    }
}
