<?php declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use App\SelfUpdate\Config;
use App\Dploy;

class SelfUpdateCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('self-update');
        $this->setDescription('Updates dploy to the latest version.');
        $this->addOption('channel', null, InputOption::VALUE_OPTIONAL, sprintf('Sets the channel to update dploy from, available channels: %s', implode(', ', Dploy::CHANNELS)));
        $this->addOption('setVersion', null, InputOption::VALUE_OPTIONAL, 'Sets the specific version to update.');
        $this->setHelp(
        <<<EOT
The <info>self-update</info> command checks dploy.cloudpanel.io for newer
versions of dploy and if found, installs the latest.

<info>dploy self-update</info>
EOT);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $container = $this->getContainer();
            $dploy = $container->get('App\Dploy');
            $config = new Config();
            $channel = (string)$input->getOption('channel');
            if (true === empty($channel)) {
                $channel = $config->get('channel');
                $channel = (false === empty($channel) ? $channel : Dploy::CHANNEL_STABLE);
            }
            $channels = $dploy->getChannels();
            if (false === in_array($channel, $channels)) {
                throw new \Exception(sprintf('%s is not a valid channel, available channels: %s', $channel, implode(', ', Dploy::CHANNELS)));
            }
            $config->set('channel', $channel);
            $localFilename = realpath($_SERVER['argv'][0]);
            if (false === $localFilename) {
                $localFilename = $_SERVER['argv'][0];
            }
            if (false === file_exists($localFilename)) {
                throw new \Exception(sprintf('Dploy update failed: the %s is not accessible.', $localFilename));
            }
            $latest = $dploy->getLatest($channel);
            $latestVersion = $latest['version'] ?? '';
            $currentVersion = $dploy->getVersion();
            $updateVersion = $input->getOption('setVersion');
            $updateVersion = (false === empty($updateVersion) ? $updateVersion : $latestVersion);
            if ($currentVersion == $updateVersion) {
                $output->writeln(sprintf('<info>You already use the latest dploy version %s (%s channel).</info>', $updateVersion, $channel));
            } else {
                if ($currentVersion < $updateVersion) {
                    $output->writeln(sprintf('Upgrading from <info>%s</info> to <info>%s</info> (%s channel).', $currentVersion, $updateVersion, $channel));
                    $output->writeln('');
                    $downloadedFile = $dploy->downloadVersion($updateVersion, $output);
                    $publicKey = sprintf('%s/data/keys/public.key', $this->getProjectDirectory());
                    $opensslPublicKey = openssl_pkey_get_public(file_get_contents($publicKey));
                    if (false === $opensslPublicKey) {
                        throw new \RuntimeException(sprintf('Failed loading the public key from: %s', $publicKey));
                    }
                    $signature = $dploy->getSignatureForVersion($updateVersion);
                    $verified = 1 === openssl_verify((string) file_get_contents($downloadedFile), $signature, $opensslPublicKey, OPENSSL_ALGO_SHA384);
                    if (false === $verified) {
                        throw new \RuntimeException('The phar signature did not match the file you downloaded, this means your public keys are outdated or that the phar file is corrupt/has been modified.');
                    }
                    @copy($downloadedFile, $localFilename);
                    $output->writeln(str_repeat(PHP_EOL, 1));
                    exit(Command::SUCCESS);
                }
            }
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln(PHP_EOL.PHP_EOL.sprintf('<error>%s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}