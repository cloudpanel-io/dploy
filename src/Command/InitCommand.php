<?php declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;
use App\Deployment\Setup as DeploymentSetup;
use App\Deployment\Config as DeploymentConfig;

class InitCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('init');
        $this->setDescription('Setups the project directory structure.');
        $this->addArgument('application', InputArgument::OPTIONAL, 'Downloads a config for the application.', 'generic');
        $this->setHelp(
            <<<EOT
The <info>init</info> command downloads a pre-configured <info>config.yml</info> from 

<comment>https://github.com/cloudpanel-io/dploy-application-templates</comment>

<info>dploy init laravel</info>
EOT);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $applicationName = trim($input->getArgument('application'));
            $filesystem = new Filesystem();
            $config = new DeploymentConfig();
            $systemUserId = $config->getSystemUserId();
            if (0 == $systemUserId) {
                throw new \Exception('Not allowed to run the command as root, use the site user.');
            }
            $configFile = $config->getConfigFile();
            if (false === $filesystem->exists($configFile)) {
                $templates = $this->getTemplates();
                if (false === isset($templates[$applicationName])) {
                    throw new \Exception(sprintf('Template %s does not exist, available templates: %s', $applicationName, implode(',', array_keys($templates))));
                }
                if (true === function_exists('xdebug_is_debugger_active') && true === xdebug_is_debugger_active()) {
                    $gitRepository = $_ENV['APP_DEV_GIT_REPOSITORY'];
                    $deployDirectory = $_ENV['APP_DEV_DEPLOY_DIRECTORY'];
                } else {
                    $helper = $this->getHelper('question');
                    $gitRepositoryQuestion = new Question('Git Repository: ');
                    $gitRepository = $helper->ask($input, $output, $gitRepositoryQuestion);
                    $output->writeln(sprintf('<info>Deploy directory like: /home/%s/htdocs/www.domain.com</info>', $config->getSystemUserName()));
                    $deployDirectoryQuestion = new Question('Deploy Directory: ');
                    $deployDirectory = $helper->ask($input, $output, $deployDirectoryQuestion);
                }
                $deployDirectory = rtrim($deployDirectory, '/');
                $template = str_replace(['{git_repository}', '{deploy_directory}'], [$gitRepository, $deployDirectory], $templates[$applicationName]);
                $tmpFile = tmpfile();
                $tmpFilePath = stream_get_meta_data($tmpFile)['uri'];
                file_put_contents($tmpFilePath, $template);
                $configDirectory = $config->getConfigDirectory();
                $filesystem = new Filesystem();
                $filesystem->mkdir($configDirectory, 0770);
                $filesystem->copy($tmpFilePath, $configFile);
                $setup = new DeploymentSetup($config);
                $setup->create();
                $output->writeln(sprintf('<comment>%s</comment>', sprintf('The config "%s" has been created.', $configFile)));
            } else {
                throw new \Exception(sprintf('Config file %s already exists, nothing to do.', $configFile));
            }
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
    }

    private function getTemplates(): array
    {
        $templates = [];
        try {
            $filesystem = new Filesystem();
            $tmpDirectory = sprintf('%s/%s', rtrim(sys_get_temp_dir(), '/'), uniqid());
            $gitCloneCommand = sprintf('/usr/bin/git clone %s %s', self::TEMPLATES_GITHUB_REPOSITORY, $tmpDirectory);
            $process = Process::fromShellCommandline($gitCloneCommand);
            $process->setTimeout(600);
            $process->run();
            $directoryIterator = new \DirectoryIterator($tmpDirectory);
            foreach ($directoryIterator as $fileInfo) {
                $name = $fileInfo->getFilename();
                $filePath = $fileInfo->getPathname();
                if (false === empty($name) && true === is_file($filePath) && true === file_exists($filePath)) {
                    $templates[$name] = file_get_contents($filePath);
                }
            }
        } catch (\Exception $e) {
            throw $e;
        } finally {
            if (true === isset($tmpDirectory) && true === is_dir($tmpDirectory)) {
                $filesystem->remove($tmpDirectory);
            }
        }
        return $templates;
    }
}