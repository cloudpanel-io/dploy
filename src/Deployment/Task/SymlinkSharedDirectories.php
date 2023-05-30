<?php

namespace App\Deployment\Task;

use App\Deployment\Task\Task as BaseTask;

class SymlinkSharedDirectories extends BaseTask
{
    protected string $description = 'Symlink Shared Directories';

    public function run(): void
    {
        $deployment = $this->getDeployment();
        $config = $deployment->getConfig();
        $releaseDirectory = $deployment->getReleaseDirectory();
        $sharedDirectories = $config->getSharedDirectories();
        if (false === empty($sharedDirectories)) {
            foreach ($sharedDirectories as $sharedDirectory) {
                $sharedDirectory = rtrim(ltrim($sharedDirectory, '/'), '/');
                $sharedDirectoryPath = sprintf('%s/%s', rtrim($releaseDirectory, '/'), $sharedDirectory);
                $deleteDestinationCommand = sprintf('rm -rf %s', $sharedDirectoryPath);
                $this->runCommand($deleteDestinationCommand);
                $symlinkCommand = sprintf('cd %s && ln -sfrn ../../shared/%s %s', $releaseDirectory, $sharedDirectory, $sharedDirectory);
                $this->runCommand($symlinkCommand);
            }
        }
    }
}