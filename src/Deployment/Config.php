<?php

namespace App\Deployment;

use Symfony\Component\Yaml\Yaml;

class Config
{
    private array $data = [];
    private ?bool $configParsed = null;

    public function getGitRepository(): ?string
    {
        return $this->getValue('git_repository');
    }

    public function getDeployDirectory(): ?string
    {
        $deployConfig = $this->getValue('deploy');
        $deployDirectory = $deployConfig['directory'] ?? '';
        return $deployDirectory;
    }

    public function getOverlaysDirectory(): string
    {
        $deployConfigDirectory = $this->getConfigDirectory();
        $overlaysDirectory = sprintf('%s/overlays', $deployConfigDirectory);
        return $overlaysDirectory;
    }

    public function getConfigDirectory(): string
    {
        $systemUserName = $this->getSystemUserName();
        $configDirectory = sprintf('/home/%s/.dploy', $systemUserName);
        return $configDirectory;
    }

    public function getHomeDirectory()
    {
        $homeDirectory = $_SERVER['HOME'] ?? '';
        return $homeDirectory;
    }

    public function getConfigFile(): string
    {
       $configDirectory = $this->getConfigDirectory();
       $configFile = sprintf('%s/config.yml', $configDirectory);
       return $configFile;
    }

    public function getReleasesDirectory(): string
    {
        $deployDirectory = $this->getDeployDirectory();
        $releasesDirectory = sprintf('%s/releases', $deployDirectory);
        return $releasesDirectory;
    }

    public function getSharedDirectory(): string
    {
        $deployDirectory = $this->getDeployDirectory();
        $sharedDirectory = sprintf('%s/shared', $deployDirectory);
        return $sharedDirectory;
    }

    public function getSharedDirectories(): array
    {
        $deployConfig = $this->getValue('deploy');
        $sharedDirectories = (true == isset($deployConfig['shared_directories']) ? (array)$deployConfig['shared_directories'] : []);
        return $sharedDirectories;
    }

    public function getBeforeDeployCommands(): array
    {
        $deployConfig = $this->getValue('deploy');
        $beforeDeployCommands = (true == isset($deployConfig['before_commands']) ? (array)$deployConfig['before_commands'] : []);
        return $beforeDeployCommands;
    }

    public function getAfterDeployCommands(): array
    {
        $deployConfig = $this->getValue('deploy');
        $afterDeployCommands = (true == isset($deployConfig['after_commands']) ? (array)$deployConfig['after_commands'] : []);
        return $afterDeployCommands;
    }

    private function parseConfigFile(): void
    {
        if (true === is_null($this->configParsed)) {
            $configFile = $this->getConfigFile();
            $data = Yaml::parseFile($configFile);
            if (true === isset($data['project'])) {
                $this->data = $data['project'];
            }
            $this->configParsed = true;
        }
    }

    private function getValue(string $key): mixed
    {
        $this->parseConfigFile();
        $value = $this->data[$key] ?? '';
        return $value;
    }

    public function getSystemUserName(): string
    {
        $systemUserId = $this->getSystemUserId();
        $processUser = posix_getpwuid($systemUserId);
        $systemUserName = $processUser['name'];
        return $systemUserName;
    }
    public function getSystemUserId(): int
    {
        $systemUserId = posix_geteuid();
        return $systemUserId;
    }
}