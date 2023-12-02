<?php

namespace Hexlet\Code;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'page-loader', description: 'Download a web-page')]
class Command extends BaseCommand
{
    protected static $defaultDescription = 'Download a web-page';

    public function __construct(
        ?string $name,
        protected readonly Loader $loader
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->setDefinition(
                new InputDefinition([
                    new InputArgument('url', InputArgument::REQUIRED, 'Page url'),
                    new InputOption(
                        'output',
                        'o',
                        InputOption::VALUE_REQUIRED,
                        'Path (folder) to store a result. Default is current working directory'
                    )
                ])
            );
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $url = $input->getArgument('url');
        $targetDir = $input->getOption('output') ?? getcwd();

        if (! $this->validUrl($url)) {
            $output->writeln("<error>Incorrect URL:  $url</error>");
            return Command::INVALID;
        }

        try {
            $storeResult = $this->loader->load($url, $targetDir);
        } catch (\Exception $exception) {
            $output->writeln("<error>{$exception->getMessage()}</error>");
            return Command::FAILURE;
        }

        if ($storeResult === false) {
            $output->writeln('<error>Unknown error</error>');
            return Command::FAILURE;
        }

        $warnings = $this->loader->getWarnings();
        if (count($warnings) !== 0) {
            $output->writeln('<error>Page was loaded with errors: </error>');
            // @phpstan-ignore-next-line
            foreach ($warnings as $warning) {
                $output->writeln(" * $warning");
            }
            $output->writeln("");
            $output->writeln("<info>Loaded to {$this->loader->getResultPagePath()}</info>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Page was loaded to {$this->loader->getResultPagePath()}</info>");

        return Command::SUCCESS;
    }

    private function validUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
