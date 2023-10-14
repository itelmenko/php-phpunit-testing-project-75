<?php

namespace Hexlet\Code\Tests;

use Hexlet\Code\FilePathBuilder;
use PHPUnit\Framework\TestCase;

class FilePathBuilderTest extends TestCase
{

    /**
     * @return array<string,string[]>
     */
    public function urlsDataProvider(): array
    {
        return [
            'simple_url' => [
                'https://some.domain.net/with/path/',
                null,
                'some-domain-net-with-path'
            ],
            'url_with_file_name' => [
                'https://some.domain.net/with/path/and-dot.html',
                null,
                'some-domain-net-with-path-and-dot-html'
            ],
            'url_with_query' => [
                'https://some.domain.net/with/path/?param1=foo&param2=bar',
                null,
                'some-domain-net-with-path'
            ],
            'url_without_path' => [
                'https://some.domain.net',
                null,
                'some-domain-net'
            ],
            'file_with_extension' => [
                'https://some.domain.net/with/path/',
                'html',
                'some-domain-net-with-path.html'
            ]
        ];
    }

    /**
     * @dataProvider urlsDataProvider
     */
    public function testItConvertSpecialSymbolsToMinus(string $url, ?string $extension, string $expected): void
    {
        $pathBuilder = new FilePathBuilder();
        $actual = $pathBuilder->buildFilePath($url, $extension);
        $this->assertEquals($expected, $actual);
    }
}
