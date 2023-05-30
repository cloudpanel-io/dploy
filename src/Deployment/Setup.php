<?php

namespace App\Deployment;

use Symfony\Component\Filesystem\Filesystem;

class Setup
{
    private const DIRECTORY_CHMOD = 0760;

    public function __construct(
        private readonly Config $config
    ) {
    }

    public function create(): void
    {
        $gitRepository = $this->config->getGitRepository();
        $deployDirectory = $this->config->getDeployDirectory();
        if (true === empty($gitRepository)) {
            throw new \Exception('git repository cannot be empty.');
        }
        if (true === empty($deployDirectory)) {
            throw new \Exception('deploy directory cannot be empty.');
        }
        $filesystem = new Filesystem();
        $configDirectory = $this->config->getConfigDirectory();
        if (false === $filesystem->exists($configDirectory)) {
            $filesystem->mkdir($configDirectory, self::DIRECTORY_CHMOD);
        }
        $releasesDirectory = $this->config->getReleasesDirectory();
        if (false === $filesystem->exists($releasesDirectory)) {
            $filesystem->mkdir($releasesDirectory, self::DIRECTORY_CHMOD);
        }
        $overlaysDirectory = $this->config->getOverlaysDirectory();
        if (false === $filesystem->exists($overlaysDirectory)) {
            $filesystem->mkdir($overlaysDirectory, self::DIRECTORY_CHMOD);
        }
        $sharedDirectory = $this->config->getSharedDirectory();
        if (false === $filesystem->exists($sharedDirectory)) {
            $filesystem->mkdir($sharedDirectory, self::DIRECTORY_CHMOD);
        }
        $sharedDirectories = $this->config->getSharedDirectories();
        if (false === empty($sharedDirectories)) {
            foreach ($sharedDirectories as $directory) {
                $directory = sprintf('%s/%s', $sharedDirectory, $directory);
                if (false === $filesystem->exists($directory)) {
                    $filesystem->mkdir($directory, self::DIRECTORY_CHMOD);
                }
            }
        }
    }
}