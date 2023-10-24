<?php

namespace Hexlet\Code\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Hexlet\Code\Command;
use Hexlet\Code\DownloadException;
use Hexlet\Code\FilePathBuilder;
use Hexlet\Code\Loader;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CommandTest extends TestCase
{

    private string $targetPathUrl;

    public function setUp(): void
    {
        vfsStream::setup('base');

        $this->targetPathUrl = $this->getVirtualPath('loader');
        mkdir($this->targetPathUrl);
    }

    private function getVirtualPath(?string $path = null): string
    {
        $directoryPath = vfsStream::url('base');

        return empty($path) ? $directoryPath : "{$directoryPath}/".ltrim($path, '/');
    }

    /**
     * @param array<Response> $responses
     * @return Client
     */
    private function getClientMock(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);

        return new Client(['handler' => $handlerStack]);
    }

    private function execCommand(Client $client, string $url): void
    {
        $pathBuilder = new FilePathBuilder();

        $log = new Logger('main');
        $stackHandler = new StreamHandler(__DIR__.'/page-loader.log', Level::Debug);
        /**
         * @var LineFormatter $formatter
         */
        $formatter = $stackHandler->getFormatter();
        $formatter->ignoreEmptyContextAndExtra();
        $log->pushHandler($stackHandler);

        $loader = new Loader($client, $pathBuilder, $log);
        $command = new Command('page-loader', $loader);

        $application = new Application();
        $application->add($command);

        $command = $application->find('page-loader');
        $testerCommand = new CommandTester($command);
        $testerCommand->execute(['url' => $url, '--output' => $this->targetPathUrl]);
    }

    public function testItLoadsContent(): void
    {
        $url = 'http://some-domain.net/page/path';
        $content = '<html lang="en"><head><title></title></head><body>Sample page</body></html>';
        $httpClient = $this->getClientMock([
            new Response(202, [], $content),
        ]);

        $this->execCommand($httpClient, $url);

        $result = file_get_contents($this->getVirtualPath('loader/some-domain-net-page-path.html'));
        $this->assertEquals($content, $result);
    }

    public function testItLoadsResources(): void
    {
        $url = 'http://some-domain.com/area/page';
        $content = <<<'EOD'
        <html lang="en">
            <head>
                <title></title>
                <link rel="stylesheet" href="http://some-domain.com/assets/menu.css">
            </head>
            <body>
                Sample page
                <img src="http://some-domain.com/assets/main.png" alt="Main"/>
                <script>console.log('inline script')</script>
                <script src="http://some-domain.com/packs/js/runtime.js"></script>
            </body>
        </html>
        EOD;

        $mainImgContent = 'ddddd';
        $cssFileContent = 'body { font-size: 16px; }';
        $jsFileContent = 'console.log("Hello!")';
        $httpClient = $this->getClientMock([
            new Response(200, [], $content),
            new Response(200, [], $mainImgContent),
            new Response(200, [], $cssFileContent),
            new Response(200, [], $jsFileContent),
        ]);

        $this->execCommand($httpClient, $url);

        $resultContent = <<<'EOD'
        <html lang="en">
            <head>
                <title></title>
                <link rel="stylesheet" href="some-domain-com-area-page_files/some-domain-com-assets-menu.css">
            </head>
            <body>
                Sample page
                <img src="some-domain-com-area-page_files/some-domain-com-assets-main.png" alt="Main">
                <script>console.log('inline script')</script>
                <script src="some-domain-com-area-page_files/some-domain-com-packs-js-runtime.js"></script>
            </body>
        </html>
        EOD;
        $result = file_get_contents($this->getVirtualPath('loader/some-domain-com-area-page.html'));
        $this->assertEquals($resultContent, $result);

        $mainImg = $this->getVirtualPath('loader/some-domain-com-area-page_files/some-domain-com-assets-main.png');
        $mainImgResult = @file_get_contents($mainImg);
        $this->assertEquals($mainImgContent, $mainImgResult);

        $cssFile = $this->getVirtualPath('loader/some-domain-com-area-page_files/some-domain-com-assets-menu.css');
        $cssFileResult = @file_get_contents($cssFile);
        $this->assertEquals($cssFileContent, $cssFileResult);

        $jsFile = $this->getVirtualPath('loader/some-domain-com-area-page_files/some-domain-com-packs-js-runtime.js');
        $jsFileResult = @file_get_contents($jsFile);
        $this->assertEquals($jsFileContent, $jsFileResult);
    }

    public function testItReturnsFailureCodeIfLoaderThrowsException(): void
    {
        $loader = new class extends Loader {
            public function __construct()
            {
            }
            public function load(string $url, string $targetDir): bool
            {
                throw new DownloadException("Some http error");
            }
        };
        $command = new Command('page-loader', $loader);

        $application = new Application();
        $application->add($command);

        $command = $application->find('page-loader');
        $testerCommand = new CommandTester($command);
        $commandResult = $testerCommand->execute(['url' => 'http://some.url', '--output' => '/tmp']);

        $this->assertEquals(1, $commandResult);
        $this->assertEquals("Some http error", trim($testerCommand->getDisplay()));
    }

    public function testItReturnsFailureCodeIfResourcesHaveErrors(): void
    {
        $loader = new class extends Loader {
            public function __construct()
            {
            }
            public function load(string $url, string $targetDir): bool
            {
                $this->warning[] = "problem 1";
                $this->warning[] = "problem 2";
                $this->resultPagePath = '/tmp/path';
                return true;
            }
        };
        $command = new Command('page-loader', $loader);

        $application = new Application();
        $application->add($command);

        $command = $application->find('page-loader');
        $testerCommand = new CommandTester($command);
        $commandResult = $testerCommand->execute(['url' => 'http://some.url/path', '--output' => $this->targetPathUrl]);

        $this->assertEquals(1, $commandResult);
        $output = $testerCommand->getDisplay();
        $this->assertStringContainsString("problem 1", $output);
        $this->assertStringContainsString("problem 2", $output);
        $this->assertStringContainsString("Page was loaded with errors", $output);
    }
}
