<?php

namespace Hexlet\Code\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Hexlet\Code\FilePathBuilder;
use Hexlet\Code\Loader;
use PHPUnit\Framework\TestCase;

class LoaderTest extends TestCase
{

    public function testItLoadsContent(): void
    {
        $content = '<html><head></head><body>Sample page</body></html>';
        $url = 'http://some-domain.net/page/path';
        $mock = new MockHandler([
            new Response(202, [], $content),
        ]);

        $targetDir = '/tmp/';

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $pathBuilder = new FilePathBuilder();
        $loader = new Loader($client, $pathBuilder);
        $loader->load($url, $targetDir);

        $this->assertEquals($content, $loader->getPageContent());
    }
}
