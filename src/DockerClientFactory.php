<?php

namespace Tarekdj\DockerClient;

use TestContainersPHP\Docker\ApiClient\Client as ApiClient;
class DockerClientFactory
{
    public static function create(?string $dockerHost = null, ?bool $ssl = false, ?string $certPath = null): ApiClient
    {
        $options = [
            'remote_socket' => $dockerHost ?: \getenv('DOCKER_HOST') ?: 'unix:///var/run/docker.sock',
            'ssl' => $ssl ?: \getenv('DOCKER_TLS_VERIFY') ?: false,
        ];

        $certPath = $certPath ?: \getenv('DOCKER_CERT_PATH') ?: null;

        if ($certPath) {
            $options['stream_context_options'] = [
                'ssl' => [
                    'peer_name' => 'socket-adapter',
                    'cafile' => $certPath,
                ],
            ];
        }

        $socketClient = new Client($options);

        return ApiClient::create($socketClient);
    }
}
