<?php

namespace Hexlet\Code\Tests\DownloadPageFunction;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Hexlet\Code\Tests\FixturesService;

// @phpstan-ignore-next-line
class TestHttpClient extends Client
{
    /**
     * @param array<mixed> $config
     */
    public function __construct(array $config = [])
    {
        $responses = [
            new Response(200, [], $this->getStubPageContent()),
            new Response(200, [], $this->getStubImageContent()),
            new Response(200, [], $this->getStubCssContent()),
            new Response(200, [], $this->getStubJsContent()),
        ];

        $handlerStack = HandlerStack::create(new MockHandler($responses));
        parent::__construct(array_merge($config, ['handler' => $handlerStack]));
    }

    public static function getStubPageContent(): string
    {
        return (new FixturesService('function'))->getFixture('source_page_with_absolute_urls.html');
    }

    public static function getStubImageContent(): string
    {
        return 'image content';
    }

    public static function getStubCssContent(): string
    {
        return 'body { font-size: 16px; }';
    }

    public static function getStubJsContent(): string
    {
        return 'console.log("Hello!")';
    }
}
