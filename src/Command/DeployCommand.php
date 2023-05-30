<?php declare(strict_types=1);

namespace App\Command;

use App\Deployment\Deployment;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;
use App\Deployment\Config as DeploymentConfig;

class DeployCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('deploy');
        $this->setHidden(false);
        $this->setDescription('Deploys a branch or tag.');
        $this->addArgument('version', InputArgument::REQUIRED, 'version');
        $this->setHelp(
            <<<EOT
<info>Examples:</info>

Deploying a branch: <comment>dploy deploy main</comment>
Deploying a tag: <comment>dploy deploy v1.0.0</comment>
EOT);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $version = trim($input->getArgument('version'));
            $config = new DeploymentConfig();
            $systemUserId = $config->getSystemUserId();
            if (0 == $systemUserId) {
                throw new \Exception('Not allowed to run the command as root, use the site user.');
            }
            $filesystem = new Filesystem();
            $configFile = $config->getConfigFile();
            if (true === $filesystem->exists($configFile)) {
                $deployment = new Deployment($output, $config, $version);
                $deployment->deploy();
                $output->writeln('<info>Deployment has been completed!</info>');
            } else {
                throw new \Exception(sprintf('Config file "%s" does not exist. Did you run dploy setup $template?', $configFile));
            }
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}