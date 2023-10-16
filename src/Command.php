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
        protected readonly Loader $loader,
        protected readonly FilePathBuilder $pathBuilder,
        protected readonly Storage $storage
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

        $this->log("url=$url");
        $this->log("output=$targetDir");

        $this->loader->load($url);
        $content = $this->loader->getPageContent();
        $filePath = $this->pathBuilder->buildFilePath($url, 'html');
        $resultPath = rtrim($targetDir, '/').'/'.$filePath;

        $output->write("Page was loaded to $resultPath");

        return $this->storage->write($content, $resultPath) ? Command::SUCCESS : Command::FAILURE;

        // or return this to indicate incorrect command usage; e.g. invalid options
        // or missing arguments (it's equivalent to returning int(2))
        // return Command::INVALID
    }

    protected function log(string $message): void
    {
        file_put_contents('page-loader.log', date('Y-m-d H:m:s').': '.$message.PHP_EOL, FILE_APPEND);
    }
}
