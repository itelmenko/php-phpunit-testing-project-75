<?php

namespace Hexlet\Code\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
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

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $loader = new Loader($client);
        $loader->load($url);

        $this->assertEquals($content, $loader->getPageContent());
    }
}
