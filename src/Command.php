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

        $output->writeln("<info>Page was loaded to {$this->loader->getResultPagePath()}</info>");

        return Command::SUCCESS;

        // or return this to indicate incorrect command usage; e.g. invalid options
        // or missing arguments (it's equivalent to returning int(2))
        // return Command::INVALID
    }
}
