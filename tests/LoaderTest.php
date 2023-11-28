<?php

namespace Hexlet\Code\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Hexlet\Code\DownloadException;
use Hexlet\Code\FilePathBuilder;
use Hexlet\Code\Loader;
use Hexlet\Code\StoreException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class LoaderTest extends TestCase
{

    public function setUp(): void
    {
        vfsStream::setup('base');
    }

    private function getLoader(MockHandler $mock): Loader
    {
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $pathBuilder = new FilePathBuilder();

        return new Loader($client, $pathBuilder);
    }

    public function testLoaderThrowExceptionOnClientHttpError(): void
    {
        echo 'testLoaderThrowExceptionOnClientHttpError()'.PHP_EOL;

        $url = 'http://some-domain.net/unknown';
        $mock = new MockHandler([
            new Response(404, [], ''),
        ]);

        $loader = $this->getLoader($mock);

        $this->expectException(DownloadException::class);
        $this->expectExceptionCode(1001);
        $this->expectExceptionMessage("It is not possible to get the page $url");

        $loader->load($url, '/tmp/');
    }

    public function testLoaderThrowExceptionOnFileSystemError(): void
    {
        $url = 'http://some-domain.net/simple';
        $dir = vfsStream::url('base');
        chmod($dir, 000);

        $mock = new MockHandler([
            new Response(200, [], '<html lang="en"></html>'),
        ]);

        $loader = $this->getLoader($mock);

        $this->expectException(StoreException::class);
        $this->expectExceptionCode(1002);
        $this->expectExceptionMessageMatches("/It is not possible to store files in path.+/");

        $loader->load($url, $dir);
    }

    public function testLoaderHasWarningsIfResourcesHaveErrors(): void
    {
        $url = 'http://some-domain.net/resources';
        $dir = vfsStream::url('base');

        $mock = new MockHandler([
            new Response(
                200,
                [],
                <<<'EOD'
                <html lang="en">
                    <body>
                        <img src="http://some-domain.net/error1">
                        <img src="http://some-domain.net/good">
                        <img src="http://some-domain.net/error2">
                    </body>
                </html>
                EOD
            ),
            new Response(500, []),
            new Response(200, [], 'image'),
            new Response(500, []),
        ]);

        $loader = $this->getLoader($mock);
        $loader->load($url, $dir);

        $this->assertCount(2, $loader->getWarnings());
        $this->assertStringContainsString('It is not possible to download resource', $loader->getWarnings()[0]);
        $this->assertStringContainsString('It is not possible to download resource', $loader->getWarnings()[1]);
        $this->assertStringContainsString('http://some-domain.net/error1', $loader->getWarnings()[0]);
        $this->assertStringContainsString('http://some-domain.net/error2', $loader->getWarnings()[1]);
    }
}
