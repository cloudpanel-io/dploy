<?php declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Descriptor\ApplicationDescription;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Helper\Helper;
use App\Command\Command as BaseCommand;

class ListCommand extends BaseCommand
{
    protected OutputInterface $output;

    protected function configure()
    {
        $this
            ->setName('list')
            ->setDefinition([
                new InputArgument('namespace', InputArgument::OPTIONAL, 'The namespace name', null, function () {
                    return array_keys((new ApplicationDescription($this->getApplication()))->getNamespaces());
                }),
                new InputOption('raw', null, InputOption::VALUE_NONE, 'To output raw command list'),
                new InputOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (txt, xml, json, or md)', 'txt', function () {
                    return (new DescriptorHelper())->getFormats();
                }),
                new InputOption('short', null, InputOption::VALUE_NONE, 'To skip describing commands\' arguments'),
            ])
            ->setDescription('List commands')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command lists all commands:

  <info>%command.full_name%</info>

You can also display the commands for a specific namespace:

  <info>%command.full_name% test</info>

You can also output the information in other formats by using the <comment>--format</comment> option:

  <info>%command.full_name% --format=xml</info>

It's also possible to get raw list of commands (useful for embedding command runner):

  <info>%command.full_name% --raw</info>
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $application = $this->getApplication();
        if ('' != $help = $application->getHelp()) {
            $output->writeln($help.PHP_EOL);
        }
        $output->writeln('<comment>Usage:</comment>');
        $output->writeln('  command [options] [arguments]'.PHP_EOL);
        $output->writeln('<comment>Available commands:</comment>');
        $commands = $application->getCommands();
        $width = $this->getColumnWidth($commands);
        foreach ($commands as $command) {
            if (false === ($command instanceof BaseCommand) || (true === $command->isHidden())) {
                continue;
            }
            $name = $command->getName();
            $spacingWidth = $width - Helper::width($name);
            $output->writeln(sprintf('  <info>%s</info>%s %s', $name, str_repeat(' ', $spacingWidth), $command->getDescription()));
        }
        return 0;
    }

    private function getColumnWidth(array $commands): int
    {
        $widths = [];
        foreach ($commands as $command) {
            if (false === ($command instanceof BaseCommand)) {
                continue;
            }
            if ($command instanceof BaseCommand) {
                $widths[] = Helper::width($command->getName());
                foreach ($command->getAliases() as $alias) {
                    $widths[] = Helper::width($alias);
                }
            } else {
                $widths[] = Helper::width($command);
            }
        }
        return $widths ? max($widths) + 2 : 0;
    }
}