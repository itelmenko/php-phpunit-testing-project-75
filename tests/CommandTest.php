<?php

namespace Hexlet\Code\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Hexlet\Code\Command;
use Hexlet\Code\FilePathBuilder;
use Hexlet\Code\Loader;
use Hexlet\Code\Storage;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CommandTest extends TestCase
{

    public function setUp(): void
    {
        vfsStream::setup('base');
    }

    private function getVirtualPath(?string $path = null): string
    {
        $directoryPath = vfsStream::url('base');

        return empty($path) ? $directoryPath : "{$directoryPath}/".ltrim($path, '/');
    }

    public function testItLoadsContent(): void
    {
        $content = '<html><head></head><body>Sample page</body></html>';
        $url = 'http://some-domain.net/page/path';
        $mock = new MockHandler([
            new Response(202, [], $content),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $targetPathUrl = $this->getVirtualPath('loader');
        mkdir($targetPathUrl);

        $loader = new Loader($client);
        $application = new Application();
        $command = new Command('page-loader', $loader, new FilePathBuilder(), new Storage());
        $application->add($command);

        $command = $application->find('page-loader');
        $testerCommand = new CommandTester($command);
        $testerCommand->execute(['url' => $url, '--output' => $targetPathUrl]);

        $result = @file_get_contents('/tmp/some-domain-net-page-path.html');
        $this->assertEquals($content, $result);
    }
}
