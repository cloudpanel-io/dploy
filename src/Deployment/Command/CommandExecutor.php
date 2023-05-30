<?php

namespace App\Deployment\Command;

use App\System\Command;
use App\System\Process;

class CommandExecutor
{
    public function execute(Command $command, $timeout = 30): void
    {
        try {
            $runInBackground = $command->runInBackground();
            $process = Process::fromShellCommandline($command->getCommand(), '/tmp/');
            $process->setCommand($command);
            if (true === $runInBackground) {
                $process->start();
            } else {
                $process->setTimeout($timeout);
                $process->run();
                if (false === $process->isSuccessful()) {
                    throw new \RuntimeException($process->getErrorOutput());
                }
            }
        } catch (\Exception $e) {
            $fullCommand = $command->getCommand();
            $errorMessage = sprintf('Command "%s : %s" failed, error message: %s', $command->getName(), $fullCommand, $e->getMessage());
            throw new \Exception($errorMessage);
        }
    }
}