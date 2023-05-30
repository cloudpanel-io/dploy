<?php

namespace App\Deployment\Task;

use App\Deployment\Task\Task as BaseTask;

class SetPermissions extends BaseTask
{
    private const DIRECTORY_CHMOD = 770;
    private const FILE_CHMOD = 660;

    protected string $description = 'Setting Permissions';

    public function run(): void
    {
        $deployment = $this->getDeployment();
        $releaseDirectory = $deployment->getReleaseDirectory();
        $command = sprintf('/usr/bin/find %s -type d -exec chmod %s {} \; && /usr/bin/find %s -type f -exec chmod %s {} \;',
            escapeshellarg($releaseDirectory),
            self::DIRECTORY_CHMOD,
            escapeshellarg($releaseDirectory),
            self::FILE_CHMOD
        );
        $this->runCommand($command);
    }
}