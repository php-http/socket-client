<?php

namespace Tarekdj\DockerClient\Tests;

use Tarekdj\DockerClient\Client;
use PHPUnit\Framework\TestCase;
use Tarekdj\DockerClient\DockerClientFactory;
use TestContainersPHP\Docker\ApiClient\Model\SystemInfo;
use TestContainersPHP\Docker\ApiClient\Model\SystemVersion;

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
}
