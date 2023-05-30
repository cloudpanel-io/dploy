<?php

namespace App\Console;

use Symfony\Bundle\FrameworkBundle\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\CompleteCommand;
use Symfony\Component\Console\Command\DumpCompletionCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Dploy;
use App\Command\InitCommand;
use App\Command\ListCommand;
use App\Command\DeployCommand;
use App\Command\SelfUpdateCommand;
class Application extends BaseApplication
{
    const APPLICATION_NAME = 'Dploy';
    const APPLICATION_LOGO = '
  _____  _____  _      ______     __
 |  __ \|  __ \| |    / __ \ \   / /
 | |  | | |__) | |   | |  | \ \_/ / 
 | |  | |  ___/| |   | |  | |\   /  
 | |__| | |    | |___| |__| | | |   
 |_____/|_|    |______\____/  |_|   
                                    
                                    
';
    private KernelInterface $kernel;
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        parent::__construct($this->kernel);
    }
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        $this->setApplicationNameAndVersion();
        return parent::doRun($input, $output);
    }
    private function setApplicationNameAndVersion(): void
    {
        $version = Dploy::getVersion();
        $this->setName(self::APPLICATION_NAME);
        $this->setVersion($version);
    }
    private function init()
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;

        foreach ($this->getDefaultCommands() as $command) {
            $this->add($command);
        }
    }
    public function getHelp(): string
    {
        return self::APPLICATION_LOGO . parent::getHelp();
    }
    public function getLongVersion(): string
    {
        $name = $this->getName();
        $version = $this->getVersion();
        $longVersion = sprintf('<info>%s</info> version <comment>%s</comment>', $name, $version);
        return $longVersion;

    }
    protected function getDefaultCommands(): array
    {
        $listCommand = new ListCommand();
        $listCommand->setHidden(true);
        $commands = [
            new HelpCommand(),
            $listCommand,
            new CompleteCommand(),
            new DumpCompletionCommand(),
            new InitCommand(),
            new DeployCommand(),
        ];
        if (true === IS_PHAR) {
            $commands[] = new SelfUpdateCommand();
        }
        return $commands;
    }
    public function getCommands(): array
    {
        return $this->getDefaultCommands();
    }
    public function getContainer(): ContainerInterface
    {
        return $this->kernel->getContainer();
    }
}