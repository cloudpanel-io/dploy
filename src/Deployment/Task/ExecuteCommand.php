<?php

namespace App\Deployment\Task;

use Symfony\Component\Process\Process;
use App\Deployment\Task\Task as BaseTask;

class ExecuteCommand extends BaseTask
{
    protected string $description = 'Executing Command';
    private string $command;

    public function run(): void
    {
        $deployment = $this->getDeployment();
        $output = $deployment->getOutput();
        $command = $this->getCommand();
        $process = Process::fromShellCommandline($command, '/tmp/');
        $process->setTimeout(3600);
        $process->run(function ($type, $buffer) use ($output) {
            if (false === empty($buffer)) {
                $output->write($buffer);
            }
        });
        if (false === $process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }

    public function getDescription(): string
    {
        $command = $this->getCommand();
        $this->description = sprintf('%s: %s', $this->description, $command);
        return $this->description;
    }

    public function setCommand(string $command): void
    {
        $this->command = $command;
    }

    public function getCommand(): string
    {
        $deployment = $this->getDeployment();
        $releaseDirectory = $deployment->getReleaseDirectory();
        if (true === str_contains($this->command, '{release_directory}')) {
            $this->command = str_replace('{release_directory}', $releaseDirectory, $this->command);
        }
        return $this->command;
    }
}