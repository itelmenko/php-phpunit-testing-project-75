<?php

namespace Hexlet\Code;

use GuzzleHttp\Client;

class Loader
{

    private ?string $content = null;

    private ?string $resultPagePath = null;

    public function __construct(private readonly Client $client, private readonly FilePathBuilder $pathBuilder)
    {
    }

    public function load(string $url, string $targetDir): bool
    {
        $this->content = $this->client->get($url)->getBody()->getContents();

        $filePath = $this->pathBuilder->buildFilePath($url, 'html');
        $this->resultPagePath = rtrim($targetDir, '/').'/'.$filePath;

        return $this->write($this->content, $this->resultPagePath);
    }

    public function getResultPagePath(): string
    {
        return $this->resultPagePath;
    }

    private function write(string $content, string $filePath): bool
    {
        return file_put_contents($filePath, $content) !== false;
    }

    public function getPageContent(): ?string
    {
        return $this->content;
    }
}
