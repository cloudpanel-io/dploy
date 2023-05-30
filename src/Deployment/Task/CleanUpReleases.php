<?php

namespace App\Deployment\Task;

use App\Deployment\Task\Task as BaseTask;

class CleanUpReleases extends BaseTask
{
    protected string $description = 'Clean Up Releases';

    public function run(): void
    {
        $deployment = $this->getDeployment();
        $config = $deployment->getConfig();
        $releasesDirectory = $config->getReleasesDirectory();
        $command = sprintf('cd %s && ls -t | tail -n +4 | xargs rm -rf', $releasesDirectory);
        $this->runCommand($command);
    }
}