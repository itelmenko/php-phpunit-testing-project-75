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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CommandTest extends TestCase
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
        $application = new Application();
        $command = new Command('page-loader', $loader, new FilePathBuilder(), new Storage());
        $application->add($command);

        $command = $application->find('page-loader');
        $testerCommand = new CommandTester($command);
        $testerCommand->execute(['url' => $url, '--output' => '/tmp']);

        $result = @file_get_contents('/tmp/some-domain-net-page-path.html');
        $this->assertEquals($content, $result);
    }
}
