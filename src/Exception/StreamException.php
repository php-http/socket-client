<?php

namespace Tarekdj\DockerClient\Exception;

use Psr\Http\Client\ClientExceptionInterface;

class StreamException extends \RuntimeException implements ClientExceptionInterface
{
}
