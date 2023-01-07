<?php

namespace Tarekdj\DockerClient\Tests;

use PHPUnit\Framework\TestCase;
use Tarekdj\Docker\ApiClient\Model\SystemInfo;
use Tarekdj\Docker\ApiClient\Model\SystemVersion;
use Tarekdj\DockerClient\DockerClientFactory;

class DockerTest extends TestCase
{
    public function testDockerClient(): void
    {
        $dockerClient = DockerClientFactory::create();
        $info = $dockerClient->systemInfo();
        $this->assertInstanceOf(SystemInfo::class, $info);
        $this->assertNotNull($info->getID());
        $version = $dockerClient->systemVersion();
        $this->assertNotNull($version->getVersion());
        $this->assertInstanceOf(SystemVersion::class, $version);
    }

    public function testDockerClientGetHost(): void
    {
        $dockerClient = DockerClientFactory::create();
        $this->assertEquals('localhost', $dockerClient->getHost());

        $dockerClient = DockerClientFactory::create('http://remote-docker-host');
        $this->assertEquals('remote-docker-host', $dockerClient->getHost());

        $dockerClient = DockerClientFactory::create('https://remote-docker-host');
        $this->assertEquals('remote-docker-host', $dockerClient->getHost());

        putenv('DOCKER_HOST=https://my-remote-docker-host.com');
        $dockerClient = DockerClientFactory::create();
        $this->assertEquals('my-remote-docker-host.com', $dockerClient->getHost());
    }
}
