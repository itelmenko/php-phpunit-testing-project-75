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
use PHPUnit\Framework\TestCase;

class LoaderTest extends TestCase
{

    private function getLoader(MockHandler $mock): Loader
    {
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);
        $pathBuilder = new FilePathBuilder();

        return new Loader($client, $pathBuilder);
    }

    public function testLoaderThrowExceptionOnClientHttpError(): void
    {
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
        $dir = '/root/';
        $mock = new MockHandler([
            new Response(200, [], '<html lang="en"></html>'),
        ]);

        $loader = $this->getLoader($mock);

        $this->expectException(StoreException::class);
        $this->expectExceptionCode(1002);
        $this->expectExceptionMessageMatches("/It is not possible to store files in path.+/");

        $loader->load($url, $dir);
    }
}
