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

        $filePath = $this->pathBuilder->buildIndexPath($url);
        $this->resultPagePath = rtrim($targetDir, '/').'/'.$filePath;

        $document = new Document($this->content);
        $images = $document->find('img');

        if (!empty($images)) {
            $imgUrl =  $images[0]->attr('src');

            $folderPath = rtrim($targetDir, '/').'/'.$this->pathBuilder->buildFolderPath($url);
            if (! file_exists($folderPath)) {
                mkdir($folderPath);
            }

            $imagePath = $folderPath.'/'.$this->pathBuilder->buildFilePath($imgUrl);
            $this->client->get($imgUrl, ['sink' =>  $imagePath]);
        }

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
