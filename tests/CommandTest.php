<?php

namespace Hexlet\Code\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Hexlet\Code\Command;
use Hexlet\Code\FilePathBuilder;
use Hexlet\Code\Loader;
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
        $loader = new Loader($client, $pathBuilder);
        $application = new Application();
        $command = new Command('page-loader', $loader);
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

    public function testItLoadsImages(): void
    {
        $url = 'http://some-domain.com/area/page';
        $content = <<<'EOD'
        <html lang="en"><head><title></title></head>
            <body>
                Sample page
                <img src="http://some-domain.com/assets/main.png" alt="Main"/>
            </body>
        </html>
        EOD;

        $mainImgContent = 'ddddd';
        $httpClient = $this->getClientMock([
            new Response(202, [], $content),
            new Response(202, [], $mainImgContent),
        ]);

        $this->execCommand($httpClient, $url);

        $result = file_get_contents($this->getVirtualPath('loader/some-domain-com-area-page.html'));
        $this->assertEquals($content, $result);

        $mainImg = $this->getVirtualPath('loader/some-domain-com-area-page_files/some-domain-com-assets-main.png');
        $mainImgResult = @file_get_contents($mainImg);
        $this->assertEquals($mainImgContent, $mainImgResult);
    }
}
