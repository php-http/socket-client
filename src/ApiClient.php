<?php

namespace Tarekdj\DockerClient;

use Nyholm\Psr7\Uri;
use Tarekdj\Docker\ApiClient\Client as DockerApiClient;

class ApiClient extends DockerApiClient
{
    public static function create($httpClient = null, array $additionalPlugins = [], array $additionalNormalizers = []): ApiClient
    {
        return parent::create($httpClient, $additionalPlugins, $additionalNormalizers);
    }

    public function getHost()
    {
        $remote = $this->httpClient->getConfig()['remote_socket'];
        $schema = explode(':', $remote);

        return match ($schema[0] ?? '') {
            'http', 'https' => (new Uri($remote))->getHost(),
            default => 'localhost',
        };
    }
}
