<?php

namespace Hexlet\Code\Tests\DownloadPageFunction;

use Hexlet\Code\Tests\FixturesService;
use Hexlet\Code\Tests\VirtualFileSystemService;
use PHPUnit\Framework\TestCase;
use function Downloader\Downloader\downloadPage;

class DownloadPageFunctionTest extends TestCase
{
    public function testItLoadsResources(): void
    {

        $vfsService = new VirtualFileSystemService('function');
        $fixturesService = new FixturesService('function');
        $targetPathUrl = $vfsService->getVirtualPath();

        $url = 'http://some-domain.com/area/page';

        $success = downloadPage($url, $targetPathUrl, TestHttpClient::class);
        $this->assertTrue($success);

        $result = file_get_contents($vfsService->getVirtualPath('some-domain-com-area-page.html'));
        $this->assertEquals($fixturesService->getFixture("result_page_with_internal_urls.html"), $result);

        $mainImg = $vfsService->getVirtualPath('some-domain-com-area-page_files/some-domain-com-assets-main.png');
        $this->assertEquals(TestHttpClient::getStubImageContent(), file_get_contents($mainImg));

        $cssFile = $vfsService->getVirtualPath('some-domain-com-area-page_files/some-domain-com-assets-menu.css');
        $this->assertEquals(TestHttpClient::getStubCssContent(), file_get_contents($cssFile));

        $jsFile = $vfsService->getVirtualPath('some-domain-com-area-page_files/some-domain-com-packs-js-runtime.js');
        $this->assertEquals(TestHttpClient::getStubJsContent(), file_get_contents($jsFile));
    }
}
