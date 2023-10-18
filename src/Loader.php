<?php

namespace Hexlet\Code;

use DiDom\Document;
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
        $this->resultPagePath = $this->getIndexPagePath($url, $targetDir);
        $folderPath = $this->getFolderPath($url, $targetDir);
        $this->loadImages($folderPath);

        return $this->write($this->content, $this->resultPagePath);
    }

    private function getIndexPagePath(string $url, string $targetDir): string
    {
        $filePath = $this->pathBuilder->buildIndexPath($url);
        return rtrim($targetDir, '/').'/'.$filePath;
    }

    private function getFolderPath(string $url, string $targetDir): string
    {
        $folderPath = rtrim($targetDir, '/').'/'.$this->pathBuilder->buildFolderPath($url);
        if (! file_exists($folderPath)) {
            mkdir($folderPath);
        }

        return $folderPath;
    }

    private function loadImages(string $folderPath): void
    {
        $document = new Document($this->content);
        $images = $document->find('img');

        foreach ($images as $image) {
            $imgUrl =  $image->attr('src');
            $imagePath = $folderPath.'/'.$this->pathBuilder->buildFilePath($imgUrl);
            $this->client->get($imgUrl, ['sink' =>  $imagePath]);
        }
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
