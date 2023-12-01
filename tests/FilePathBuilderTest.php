<?php

namespace Hexlet\Code\Tests;

use Hexlet\Code\FilePathBuilder;
use PHPUnit\Framework\TestCase;

class FilePathBuilderTest extends TestCase
{

    /**
     * @dataProvider buildFilePathDataProvider
     */
    public function testBuildFilePath(
        string $url,
        bool $keepExtension,
        ?string $defaultExtension,
        string $expected
    ): void {
        $pathBuilder = new FilePathBuilder();
        $actual = $pathBuilder->buildFilePath($url, $keepExtension, $defaultExtension);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider buildIndexPathDataProvider
     */
    public function testBuildIndexPath(string $url, string $expected): void
    {
        $pathBuilder = new FilePathBuilder();
        $actual = $pathBuilder->buildIndexPath($url, 'htm');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider buildFolderPathDataProvider
     */
    public function testBuildFolderPath(string $url, string $expected): void
    {
        $pathBuilder = new FilePathBuilder();
        $actual = $pathBuilder->buildFolderPath($url, '_data');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array<string, array<int,bool|string|null>>
     */
    public function buildFilePathDataProvider(): array
    {
        return [
            'simple_url' => [
                'https://some.domain.net/with/path/', // url
                true, // keepExtension
                null, // defaultExtension
                'some-domain-net-with-path' // expected
            ],
            'url_with_file_name_no_extension' => [
                'https://some.domain.net/with/path/and-dot.html',
                false,
                null,
                'some-domain-net-with-path-and-dot'
            ],
            'url_with_file_name_with_extension' => [
                'https://some.domain.net/with/path/and-dot.html',
                true,
                null,
                'some-domain-net-with-path-and-dot.html'
            ],
            'url_with_query' => [
                'https://some.domain.net/with/path/?param1=foo&param2=bar',
                false,
                null,
                'some-domain-net-with-path'
            ],
            'url_without_path_no_extension' => [
                'https://some.domain.net',
                false,
                null,
                'some-domain-net'
            ],
            'url_without_path_with_extension' => [
                'https://some.domain.net',
                true,
                null,
                'some-domain-net'
            ],
            'url_without_extension_for_default_extension' => [
                'https://some.domain.net/page',
                true,
                'html',
                'some-domain-net-page.html'
            ]
        ];
    }

    /**
     * @return array<string,string[]>
     */
    public function buildIndexPathDataProvider(): array
    {
        return [
            'simple_url' => [
                'https://some.domain.net/with/path/',
                'some-domain-net-with-path.htm'
            ],
            'url_with_file_name' => [
                'https://some.domain.net/with/path/and-dot.html',
                'some-domain-net-with-path-and-dot.htm'
            ],
            'url_with_query' => [
                'https://some.domain.net/with/path/?param1=foo&param2=bar',
                'some-domain-net-with-path.htm'
            ],
            'url_without_path' => [
                'https://some.domain.net',
                'some-domain-net.htm'
            ],
            'file_with_extension' => [
                'https://some.domain.net/with/path/',
                'some-domain-net-with-path.htm'
            ]
        ];
    }

    /**
     * @return array<string,string[]>
     */
    public function buildFolderPathDataProvider(): array
    {
        return [
            'simple_url' => [
                'https://some.domain.net/with/path/',
                'some-domain-net-with-path_data'
            ],
            'url_with_file_name' => [
                'https://some.domain.net/with/path/and-dot.html',
                'some-domain-net-with-path-and-dot_data'
            ],
            'url_with_query' => [
                'https://some.domain.net/with/path/?param1=foo&param2=bar',
                'some-domain-net-with-path_data'
            ],
            'url_without_path' => [
                'https://some.domain.net',
                'some-domain-net_data'
            ],
            'file_with_extension' => [
                'https://some.domain.net/with/path/',
                'some-domain-net-with-path_data'
            ]
        ];
    }
}
