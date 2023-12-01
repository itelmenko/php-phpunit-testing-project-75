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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CommandTest extends TestCase
{
    private string $targetPathUrl;

    private VirtualFileSystemService $vfsService;

    private FixturesService $fixturesService;

    public function setUp(): void
    {
        $this->vfsService = new VirtualFileSystemService('loader');
        $this->targetPathUrl = $this->vfsService->getVirtualPath();
        $this->fixturesService = new FixturesService('command');
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

    private function execCommand(Client $client, string $url): CommandTester
    {
        $pathBuilder = new FilePathBuilder();

        $log = new Logger('main');
        $stackHandler = new StreamHandler(__DIR__ . '/page-loader.log', Level::Debug);
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

        return $testerCommand;
    }

    public function testItLoadsContent(): void
    {
        $url = 'http://some-domain.net/page/path';
        $content = "<html lang=\"en\">\n<head><title></title></head>\n<body>Sample page</body>\n</html>";
        $httpClient = $this->getClientMock([
            new Response(202, [], $content),
        ]);

        $testerCommand = $this->execCommand($httpClient, $url);
        $this->assertEquals(0, $testerCommand->getStatusCode());
        $result = file_get_contents($this->vfsService->getVirtualPath('some-domain-net-page-path.html'));
        $this->assertEquals($content, $result);
    }

    /**
     * @dataProvider loadResourcesDataProvider
     */
    public function testItLoadsResources(string $sourcePageName): void
    {
        $url = 'http://some-domain.com/area/page';
        $content = $this->fixturesService->getFixture($sourcePageName);

        $mainImgContent = 'image content';
        $cssFileContent = 'body { font-size: 16px; }';
        $jsFileContent = 'console.log("Hello!")';
        $httpClient = $this->getClientMock([
            new Response(200, [], $content),
            new Response(200, [], $mainImgContent),
            new Response(200, [], $cssFileContent),
            new Response(200, [], $jsFileContent),
        ]);

        $testerCommand = $this->execCommand($httpClient, $url);

        $this->assertEquals(0, $testerCommand->getStatusCode());

        $result = file_get_contents($this->vfsService->getVirtualPath('some-domain-com-area-page.html'));
        $this->assertEquals($this->fixturesService->getFixture("result_page_with_internal_urls.html"), $result);

        $folderName = 'some-domain-com-area-page_files';

        $mainImg = $this->vfsService->getVirtualPath($folderName . '/some-domain-com-assets-main.png');
        $this->assertEquals($mainImgContent, @file_get_contents($mainImg));

        $cssFile = $this->vfsService->getVirtualPath($folderName . '/some-domain-com-assets-menu.css');
        $this->assertEquals($cssFileContent, @file_get_contents($cssFile));

        $jsFile = $this->vfsService->getVirtualPath($folderName . '/some-domain-com-packs-js-runtime.js');
        $this->assertEquals($jsFileContent, @file_get_contents($jsFile));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function loadResourcesDataProvider(): array
    {
        return [
            'page_with_absolute_url' => [
                'source_page_with_absolute_urls.html'
            ],
            'page_with_relative_url' => [
                'source_page_with_relative_urls.html'
            ],
        ];
    }

    public function testItIgnoresExternalResources(): void
    {
        $url = 'http://some-domain.com/area/page';
        $fixtureName = 'source_page_with_external_urls.html';
        $content = $this->fixturesService->getFixture($fixtureName);

        $httpClient = $this->getClientMock([
            new Response(200, [], $content),
        ]);

        $testerCommand = $this->execCommand($httpClient, $url);

        $this->assertEquals(0, $testerCommand->getStatusCode());

        $result = file_get_contents($this->vfsService->getVirtualPath('some-domain-com-area-page.html'));
        $this->assertEquals($this->fixturesService->getFixture($fixtureName), $result);
    }

    public function testItLoadsCanonicalLinkResource(): void
    {
        $url = 'http://some-domain.com/area/page';
        $content = $this->fixturesService->getFixture('source_page_with_link_canonical.html');

        $httpClient = $this->getClientMock([
            new Response(200, [], $content),
            new Response(200, [], $content),
        ]);

        $testerCommand = $this->execCommand($httpClient, $url);

        $this->assertEquals(0, $testerCommand->getStatusCode());

        $resultFixture = $this->fixturesService->getFixture('result_page_with_link_canonical.html');
        $result = file_get_contents($this->vfsService->getVirtualPath('some-domain-com-area-page.html'));
        $this->assertEquals($resultFixture, $result);
        $canonicalPage = 'some-domain-com-area-page_files/some-domain-com-area-page.html';
        $result = file_get_contents($this->vfsService->getVirtualPath($canonicalPage));
        $this->assertStringContainsString('Sample page', $result);
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

    public function testItReturnsErrorForIncorrectUrl(): void
    {
        $url = 'something';
        $httpClient = $this->getClientMock([
            new Response(202, [], ''),
        ]);

        $testerCommand = $this->execCommand($httpClient, $url);

        $this->assertEquals(2, $testerCommand->getStatusCode());
    }
}
