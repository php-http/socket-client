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

        switch ($schema[0] ?? '') {
            case 'http':
            case 'https':
                return (new Uri($remote))->getHost();
            default:
                return 'localhost';
        }
    }
}
