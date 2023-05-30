<?php declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;

abstract class Command extends BaseCommand
{
    public const TEMPLATES_GITHUB_REPOSITORY = 'https://github.com/cloudpanel-io/dploy-application-templates';

    protected function get(string $id)
    {
        return $this->getContainer()->get($id);
    }

    protected function getContainer()
    {
        return $this->getApplication()->getContainer();
    }

    protected function getKernel()
    {
        $container = $this->getContainer();
        return $container->get('kernel');
    }

    protected function getProjectDirectory(): string
    {
        $kernel = $this->getKernel();
        return $kernel->getProjectDir();
    }
}