<?php

namespace Http\Client\Socket\Exception;

use Psr\Http\Client\ClientExceptionInterface;

class StreamException extends \RuntimeException implements ClientExceptionInterface
{
}
