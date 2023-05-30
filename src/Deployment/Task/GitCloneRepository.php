<?php

namespace App\Deployment\Task;

use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use App\Deployment\Task\Task as BaseTask;

class GitCloneRepository extends BaseTask
{
    protected string $description = 'Cloning Git Repository';

    public function run(): void
    {
        $deployment = $this->getDeployment();
        $config = $deployment->getConfig();
        $gitRepository = $config->getGitRepository();
        $version = $deployment->getVersion();
        $releaseDirectory = $deployment->getReleaseDirectory();
        $command = sprintf('/usr/bin/git clone -c advice.detachedHead=false --progress --depth 1 --branch %s %s %s', escapeshellarg($version), escapeshellarg($gitRepository), escapeshellarg($releaseDirectory));
        $output = $deployment->getOutput();
        $process = Process::fromShellCommandline($command, '/tmp/');
        $process->setTimeout(600);
        $process->run(function ($type, $buffer) use ($output) {
            if (false === empty($buffer)) {
                $output->write($buffer);
            }
        });
        if (false === $process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
        $gitDirectory = sprintf('%s/.git', $releaseDirectory);
        $filesystem = new Filesystem();
        if (true === $filesystem->exists($gitDirectory)) {
            $filesystem->remove($gitDirectory);
        }
    }
}