<?php declare(strict_types=1);

namespace App\SelfUpdate;

use Symfony\Component\Filesystem\Filesystem;

class Config
{
    public function get(string $key): mixed
    {
        switch ($key) {
            case 'dploy-directory':
                $homeDirectory = $this->get('home-directory');
                $dployDirectory = sprintf('%s/.dploy', $homeDirectory);
                return $dployDirectory;
            case 'cache-directory':
                $dployDirectory = $this->get('dploy-directory');
                $cacheDirectory = sprintf('/home/%s/.cache', $dployDirectory);
                return $cacheDirectory;
            case 'home-directory':
                $homeDirectory = $_SERVER['HOME'] ?? '';
                return $homeDirectory;
            case 'channel':
                $channel = '';
                $dployDirectory = $this->get('dploy-directory');
                $channelFile = sprintf('%s/.channel', $dployDirectory);
                if (true === file_exists($channelFile)) {
                    $channel = trim(file_get_contents($channelFile));
                }
                return $channel;
            case 'channel-file':
                $dployDirectory = $this->get('dploy-directory');
                $channelFile = sprintf('%s/.channel', $dployDirectory);
                return $channelFile;
        }
    }

    public function set(string $key, string $value): void
    {
        $filesystem = new Filesystem();
        $dployDirectory = $this->get('dploy-directory');
        if (false === file_exists($dployDirectory)) {
            $filesystem->mkdir($dployDirectory, 0770);
        }
        switch ($key) {
            case 'channel':
                $channelFile = $this->get('channel-file');
                file_put_contents($channelFile, $value);
            break;

        }
    }

    static public function getSystemUserName(): string
    {
        $processUser = posix_getpwuid(posix_geteuid());
        $systemUserName = $processUser['name'];
        return $systemUserName;
    }
}