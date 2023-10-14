<?php

namespace Hexlet\Code\Tests;

use Hexlet\Code\Storage;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

class StorageTest extends TestCase
{
    /**
     * @var  vfsStreamDirectory
     */
    private vfsStreamDirectory $rootPath;

    public function setUp(): void
    {
        $this->rootPath = vfsStream::setup('base');
    }

    private function getVirtualPath(?string $path = null): string
    {
        $directoryPath = vfsStream::url('base');

        return empty($path) ? $directoryPath : "{$directoryPath}/".ltrim($path, '/');
    }

    public function testItWritesContent(): void
    {
        $content = 'Some data';
        $targetPathUrl = $this->getVirtualPath('available');
        mkdir($targetPathUrl);
        $filePathUrl = 'available/data.html';
        $contentPath = $this->getVirtualPath($filePathUrl);

        $storage = new Storage();
        $storage->write($content, $contentPath);

        $this->assertTrue($this->rootPath->hasChild($filePathUrl));
        $this->assertEquals($content, file_get_contents($contentPath));
    }

    public function testItDoesntWriteToNotExistsFolder(): void
    {
        $content = 'Some data';
        $filePathUrl = 'none/data.html';
        $contentPath = $this->getVirtualPath($filePathUrl);

        $storage = new Storage();
        $storage->write($content, $contentPath);

        $this->assertFalse($this->rootPath->hasChild($filePathUrl));
    }

    public function testItDoesntWriteToDisabledFolder(): void
    {
        $content = 'Some data';
        $targetPathUrl = $this->getVirtualPath('disabled');
        mkdir($targetPathUrl);
        $filePathUrl = 'disabled/data.html';
        $this->rootPath->getChild('disabled')->chmod(000);
        $contentPath = $this->getVirtualPath($filePathUrl);

        $storage = new Storage();
        $storage->write($content, $contentPath);

        $this->assertFalse($this->rootPath->hasChild($filePathUrl));
    }
}
