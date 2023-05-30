<?php

namespace App\Deployment;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use App\Deployment\Task\GitCloneRepository as GitCloneRepositoryTask;
use App\Deployment\Task\CopyOverlayFiles as CopyOverlayFilesTask;
use App\Deployment\Task\SymlinkSharedDirectories as SymlinkSharedDirectoriesTask;
use App\Deployment\Task\ExecuteCommand as ExecuteCommandTask;
use App\Deployment\Task\SetPermissions as SetPermissionsTask;
use App\Deployment\Task\ReleaseSwitch as ReleaseSwitchTask;
use App\Deployment\Task\CleanUpReleases as CleanUpReleasesTask;

class Deployment
{
    private ?string $releaseName = null;
    private array $tasks = [];

    public function __construct(
        private OutputInterface $output,
        private readonly Config $config,
        private readonly string $version
    ) {
    }

    public function deploy(): void
    {
        $this->validate();
        $this->executeTasks();
    }

    private function executeTasks(): void
    {
        $this->addTasks();
        foreach ($this->tasks as $task) {
            $this->output->writeln(sprintf('<comment>%s ...</comment>', $task->getDescription()));
            $task->run();
        }
    }

    private function addTasks(): void
    {
        $gitCloneRepositoryTask = new GitCloneRepositoryTask($this);
        $copyOverlayFilesTask = new CopyOverlayFilesTask($this);
        $symlinkSharedDirectoriesTask = new SymlinkSharedDirectoriesTask($this);
        $this->tasks = array_merge([
            $gitCloneRepositoryTask,
            $copyOverlayFilesTask,
            $symlinkSharedDirectoriesTask
        ], $this->tasks);
        $beforeCommands = $this->config->getBeforeDeployCommands();
        if (false === empty($beforeCommands)) {
            foreach ($beforeCommands as $command) {
                if (false === empty($command)) {
                    $executeCommandTask = new ExecuteCommandTask($this);
                    $executeCommandTask->setCommand($command);
                    $this->tasks[] = $executeCommandTask;
                }
            }
        }
        //$setPermissionsTask = new SetPermissionsTask($this);
        $releaseSwitchTask = new ReleaseSwitchTask($this);
        $this->tasks = array_merge($this->tasks, [$releaseSwitchTask]);
        $afterCommands = $this->config->getAfterDeployCommands();
        if (false === empty($afterCommands)) {
            foreach ($afterCommands as $command) {
                if (false === empty($command)) {
                    $executeCommandTask = new ExecuteCommandTask($this);
                    $executeCommandTask->setCommand($command);
                    $this->tasks[] = $executeCommandTask;
                }
            }
        }
        $cleanUpReleasesTask = new CleanUpReleasesTask($this);
        $this->tasks[] = $cleanUpReleasesTask;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    public function getReleaseName(): string
    {
        if (true === is_null($this->releaseName)) {
            $dateTime = new \DateTime('now');
            $this->releaseName = sprintf('%s-%s', $dateTime->format('Y-m-d-H-i-s'), $this->version);
        }
        return $this->releaseName;
    }

    public function getReleaseDirectory(): string
    {
        $releaseName = $this->getReleaseName();
        $releasesDirectory = $this->config->getReleasesDirectory();
        $releaseDirectory = sprintf('%s/%s', $releasesDirectory, $releaseName);
        return $releaseDirectory;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    private function validate(): void
    {
        $filesystem = new Filesystem();
        $deployDirectory = $this->config->getDeployDirectory();
        $systemUserName = $this->config->getSystemUserName();
        if (false === $filesystem->exists($deployDirectory)) {
            throw new \Exception(sprintf('Deploy directory "%s" does not exist.', $deployDirectory));
        }
        if (false === str_starts_with($deployDirectory, sprintf('/home/%s/htdocs', $systemUserName))) {
            throw new \Exception(sprintf('System User "%s" is not part of the deploy directory: "%s"', $systemUserName, $deployDirectory));
        }
        $overlaysDirectory = $this->config->getOverlaysDirectory();
        if (false === $filesystem->exists($overlaysDirectory)) {
            throw new \Exception(sprintf('Overlays directory "%s" does not exist.', $overlaysDirectory));
        }
        $gitRepository = $this->config->getGitRepository();
        if (true === empty($gitRepository)) {
            throw new \Exception('Git repository cannot be empty.');
        }
    }
}