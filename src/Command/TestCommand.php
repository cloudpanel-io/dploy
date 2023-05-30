<?php declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class TestCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('test');
        $this->setHidden(false);
        $this->setDescription('dploy test');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {



            /*
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
            */

            //$output->writeln(sprintf('Muha: %s', rand(1,1000)));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $output->writeln(sprintf('<error>An error has occurred: "%s"</error>', $errorMessage));
            return Command::FAILURE;
        }
    }
}