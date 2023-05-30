<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot()
    {
        date_default_timezone_set('UTC');
        setlocale(LC_CTYPE, "en_US.UTF-8");
        parent::boot();
    }

    private function getHomeDirectory()
    {
        $homeDirectory = $_SERVER['HOME'];
        return $homeDirectory;
    }

    public function getCacheDir(): string
    {
        $homeDirectory = $this->getHomeDirectory();
        $cacheDir = sprintf('%s/.dploy/.app/cache', $homeDirectory);
        return $cacheDir;
    }

    public function getLogDir(): string
    {
        $homeDirectory = $this->getHomeDirectory();
        $logDir = sprintf('%s/.dploy/.app/logs', $homeDirectory);
        return $logDir;
    }
}