<?php declare(strict_types=1);

namespace App;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class Dploy
{
    private const BASE_URL = 'https://dploy.cloudpanel.io';
    private const REQUEST_TIMEOUT = '30';
    public const CHANNEL_STABLE = 'stable';
    public const CHANNELS = ['preview', 'stable'];
    private float $percentageDownloaded = 0;
    private ?string $tmpFile;
    public function __construct(
        private HttpClientInterface $httpClient,
    )
    {
    }
    public function downloadVersion(string $version, OutputInterface $output): string
    {
        $downloadedFile = '';
        $remoteFile = sprintf('%s/download/%s/dploy', self::BASE_URL, $version);
        $progressBar = new ProgressBar($output, 100);
        $progressBar->setFormat('verbose');
        $this->percentageDownloaded = 0;
        $response = $this->httpClient->request('GET', $remoteFile, [
            'on_progress' => function (int $downloadedInBytes, int $downloadFilesizeInBytes, array $info) use ($progressBar): void {
                if ($downloadedInBytes > 0) {
                    $downloadedInMegabyte = round($downloadedInBytes/1000000);
                    $downloadFilesizeInMegabyte = round($downloadFilesizeInBytes/1000000);
                    $percentageDownloaded = (int)round(($downloadedInMegabyte/$downloadFilesizeInMegabyte)*100);
                    if ($percentageDownloaded > 0 && $percentageDownloaded > $this->percentageDownloaded) {
                        $this->percentageDownloaded = $percentageDownloaded;
                        $progressBar->setProgress($percentageDownloaded);
                    }
                }
            },
        ]);
        if (200 == $response->getStatusCode()) {
            $this->tmpFile = tempnam(sys_get_temp_dir(), '');
            $tmpFile = sprintf('%s.phar', $this->tmpFile);
            rename($this->tmpFile, $tmpFile);
            $this->tmpFile = $tmpFile;
            $body = $response->getContent();
            file_put_contents($this->tmpFile, $body);
            $downloadedFile = $this->tmpFile;
        } else {
            throw new \Exception(sprintf('Cannot download file %s, status code: %s', $remoteFile, $response->getStatusCode()));
        }
        return $downloadedFile;
    }

    public function getSignatureForVersion(string $version): string
    {
        $signature = '';
        $signatureFile = sprintf('%s/download/%s/dploy.sig', self::BASE_URL, $version);
        $response = $this->httpClient->request('GET', $signatureFile, ['timeout' => self::REQUEST_TIMEOUT]);
        $statusCode = $response->getStatusCode();
        if (200 == $statusCode) {
            $signature = (string)$response->getContent();
            $signature = base64_decode($signature);
        } else {
            throw new \Exception(sprintf('Signature file %s not available.', $signatureFile));
        }
        return $signature;
    }
    public function getLatest(string $channel)
    {
        $latest = $this->getVersionsData($channel);
        return $latest;
    }

    private function getVersionsData(string $channel): array
    {
        $requestUrl = sprintf('%s/versions.json', self::BASE_URL);
        $response = $this->httpClient->request('GET', $requestUrl, ['timeout' => self::REQUEST_TIMEOUT]);
        $statusCode = $response->getStatusCode();
        if (200 == $statusCode) {
            $versionsData = (array)$response->toArray();
            if (true === isset($versionsData[$channel]) && false === empty($versionsData[$channel])) {
                $versionsData = $versionsData[$channel];
                return $versionsData;
            } else {
                throw new \Exception(sprintf('No versions data available for channel %s.', $channel));
            }
        } else {
            throw new \Exception(sprintf('Versions file %s not available.', $requestUrl));
        }
    }

    static public function getChannels(): array
    {
        return self::CHANNELS;
    }

    static public function getVersion(): string
    {
        $version = $_ENV['APP_VERSION'] ?? '';
        return $version;
    }

    public function __destruct()
    {
        if (true === isset($this->tmpFile) && true === file_exists($this->tmpFile)) {
            @unlink($this->tmpFile);
        }
    }
}