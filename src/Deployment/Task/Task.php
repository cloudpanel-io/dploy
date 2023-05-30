<?php

namespace App\Deployment\Task;

use Symfony\Component\Process\Process;
use App\Deployment\Deployment;

abstract class Task
{
    protected string $description;

    public function __construct(
        private Deployment $deployment
    )
    {
    }

    public function run()
    {
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDeployment(): Deployment
    {
        return $this->deployment;
    }

    protected function runCommand(string $command, $timeout = 900)
    {
        $process = Process::fromShellCommandline($command, '/tmp/');
        $process->setTimeout($timeout);
        $process->run();
        if (false === $process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }
}