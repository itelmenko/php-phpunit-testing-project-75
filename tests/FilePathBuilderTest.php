<?php

namespace Hexlet\Code\Tests;

use Hexlet\Code\FilePathBuilder;
use PHPUnit\Framework\TestCase;

class FilePathBuilderTest extends TestCase
{

    public function urlsDataProvider(): array
    {
        return [
            'simple_url' => [
                'https://some.domain.net/with/path/',
                'some-domain-net-with-path'
            ],
            'url_with_file_name' => [
                'https://some.domain.net/with/path/and-dot.html',
                'some-domain-net-with-path-and-dot-html'
            ],
            'url_with_query' => [
                'https://some.domain.net/with/path/?param1=foo&param2=bar',
                'some-domain-net-with-path'
            ]
        ];
    }

    /**
     * @dataProvider urlsDataProvider
     */
    public function testItConvertSpecialSymbolsToMinus($url, $expected): void
    {
        $pathBuilder = new FilePathBuilder();
        $actual = $pathBuilder->buildFilePath($url);
        $this->assertEquals($expected, $actual);
    }
}
