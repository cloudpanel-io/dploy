<?php

namespace App\Deployment\Task;

use App\Deployment\Task\Task as BaseTask;

class ReleaseSwitch extends BaseTask
{
    protected string $description = 'Switching Release';

    public function run(): void
    {
        $deployment = $this->getDeployment();
        $config = $deployment->getConfig();
        $deployDirectory = $config->getDeployDirectory();
        $releaseDirectory = $deployment->getReleaseDirectory();
        $currentDirectory = sprintf('%s/current', $deployDirectory);
        $command = sprintf('ln -sfn  %s %s', $releaseDirectory, $currentDirectory);
        $this->runCommand($command);
    }
}