<?php

namespace Tarekdj\DockerClient\Tests;

use Http\Client\Common\Plugin\AddHostPlugin;
use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Common\Plugin\DecoderPlugin;
use Http\Client\Common\PluginClientFactory;
use Tarekdj\DockerClient\Client;
use PHPUnit\Framework\TestCase;
use Nyholm\Psr7\Uri;
use TestContainersPHP\Docker\ApiClient\Model\SystemInfo;
use TestContainersPHP\Docker\ApiClient\Model\SystemVersion;

class DockerTest extends TestCase
{
    public function testDockerClient(): void
    {
        $socketClient = new Client(['remote_socket' => 'unix:///var/run/docker.sock']);

        $dockerClient = \TestContainersPHP\Docker\ApiClient\Client::create($socketClient);
        $info = $dockerClient->systemInfo();
        $this->assertInstanceOf(SystemInfo::class, $info);
        $version = $dockerClient->systemVersion();
        $this->assertInstanceOf(SystemVersion::class, $version);

    }
}
