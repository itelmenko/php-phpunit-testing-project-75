<?php

namespace Hexlet\Code\Tests;

use org\bovigo\vfs\vfsStream;

class VirtualFileSystemService
{
    private const ROOT_DIR_NAME = 'base';

    public function __construct(private string $basePath)
    {
        vfsStream::setup(self::ROOT_DIR_NAME);
        mkdir($this->getVirtualPath());
    }

    public function getVirtualPath(?string $path = null): string
    {
        $directoryPath = vfsStream::url(self::ROOT_DIR_NAME);
        $directoryPath = rtrim($directoryPath, '/') . '/' . $this->basePath;

        return is_null($path) ? $directoryPath : "{$directoryPath}/" . ltrim($path, '/');
    }
}
