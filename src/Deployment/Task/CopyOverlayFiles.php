<?php

namespace App\Deployment\Task;

use Symfony\Component\Filesystem\Filesystem;
use App\Deployment\Task\Task as BaseTask;

class CopyOverlayFiles extends BaseTask
{
    protected string $description = 'Copying Overlay Files';

    public function run(): void
    {
        $deployment = $this->getDeployment();
        $config = $deployment->getConfig();
        $releaseDirectory = $deployment->getReleaseDirectory();
        $overlaysDirectory = $config->getOverlaysDirectory();
        $filesystem = new Filesystem();
        $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($overlaysDirectory), \RecursiveIteratorIterator::SELF_FIRST);
        foreach($objects as $object){
            if (true === $object->isFile()) {
                $originFile = $object->getRealPath();
                $targetFile = str_replace($overlaysDirectory, $releaseDirectory, $originFile);
                $filesystem->copy($originFile, $targetFile);
            }
        }
    }
}