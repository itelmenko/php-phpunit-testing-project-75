<?php

namespace Hexlet\Code;

use DiDom\Document;
use GuzzleHttp\Client;

class Loader
{

    private ?string $resultPagePath = null;

    public function __construct(private readonly Client $client, private readonly FilePathBuilder $pathBuilder)
    {
    }

    public function load(string $url, string $targetDir): bool
    {
        $sourceContent = $this->client->get($url)->getBody()->getContents();
        $this->resultPagePath = $this->getIndexPagePath($url, $targetDir);
        $folderPath = $this->getFolderPath($url, $targetDir);

        $document = new Document($sourceContent);
        $this->loadImages($document, $folderPath);
        $this->loadCssFiles($document, $folderPath);
        $this->loadJavaScriptFiles($document, $folderPath);

        $resultContent = $document->html();

        return $this->write($resultContent, $this->resultPagePath);
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

    private function loadImages(Document $document, string $absoluteFolderPath): void
    {
        $this->loadResources($document, 'img', 'src', $absoluteFolderPath);
    }

    private function loadCssFiles(Document $document, string $absoluteFolderPath): void
    {
        $this->loadResources($document, 'link[rel="stylesheet"]', 'href', $absoluteFolderPath);
    }

    private function loadJavaScriptFiles(Document $document, string $absoluteFolderPath): void
    {
        $this->loadResources($document, 'script', 'src', $absoluteFolderPath);
    }

    private function loadResources(
        Document $document,
        string   $cssSelector,
        string   $htmlAttribute,
        string   $absoluteFolderPath
    ): void {
        $elements = $document->find($cssSelector);
        foreach ($elements as $element) {
            $elementUrl =  $element->attr($htmlAttribute);
            if (empty($elementUrl)) {
                continue;
            }
            $fileName = $this->pathBuilder->buildFilePath($elementUrl);
            $filePath = $absoluteFolderPath.'/'.$fileName;
            $relativeImagePath = pathinfo($absoluteFolderPath, PATHINFO_FILENAME).'/'.$fileName;
            $this->client->get($elementUrl, ['sink' =>  $filePath]);
            $element->setAttribute($htmlAttribute, $relativeImagePath);
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
}
