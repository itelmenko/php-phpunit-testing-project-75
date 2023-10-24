<?php

namespace Hexlet\Code;

use DiDom\Document;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Psr\Log\LoggerInterface;

class Loader
{

    private ?string $resultPagePath = null;

    public function __construct(
        private readonly Client $client,
        private readonly FilePathBuilder $pathBuilder,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    public function load(string $url, string $targetDir): bool
    {
        $this->logger?->info("Url: $url. Output directory: $targetDir");
        $this->logger?->info("Download main page ...");
        $sourceContent = $this->loadIndexPage($url);

        $this->resultPagePath = $this->getIndexPagePath($url, $targetDir);
        $folderPath = $this->getFolderPath($url, $targetDir);

        $document = new Document($sourceContent);
        $this->loadImages($document, $folderPath);
        $this->loadCssFiles($document, $folderPath);
        $this->loadJavaScriptFiles($document, $folderPath);

        $resultContent = $document->html();

        return $this->write($resultContent, $this->resultPagePath);
    }

    private function loadIndexPage(string $url): string
    {
        try {
            return $this->client->get($url)->getBody()->getContents();
        } catch (TransferException $transferException) {
            throw new DownloadException(
                "It is not possible to get the page $url",
                1001,
                $transferException
            );
        }
    }

    private function getIndexPagePath(string $url, string $targetDir): string
    {
        $filePath = $this->pathBuilder->buildIndexPath($url);
        return rtrim($targetDir, '/').'/'.$filePath;
    }

    private function getFolderPath(string $url, string $targetDir): string
    {
        $folderPath = rtrim($targetDir, '/').'/'.$this->pathBuilder->buildFolderPath($url);

        if (!file_exists($folderPath)) {
            $result = @mkdir($folderPath);
            if ($result !== true) {
                $this->throwStoreException($folderPath);
            }
        }

        return $folderPath;
    }

    private function loadImages(Document $document, string $absoluteFolderPath): void
    {
        $this->logger?->info("Download images ...");
        $this->loadResources($document, 'img', 'src', $absoluteFolderPath);
    }

    private function loadCssFiles(Document $document, string $absoluteFolderPath): void
    {
        $this->logger?->info("Download css-files ...");
        $this->loadResources($document, 'link[rel="stylesheet"]', 'href', $absoluteFolderPath);
    }

    private function loadJavaScriptFiles(Document $document, string $absoluteFolderPath): void
    {
        $this->logger?->info("Download js-files ...");
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
            $this->logger?->debug("Download $elementUrl to $filePath");
            $this->client->get($elementUrl, ['sink' =>  $filePath]);
            $element->setAttribute($htmlAttribute, $relativeImagePath);
        }
    }

    public function getResultPagePath(): string
    {
        return $this->resultPagePath;
    }

    private function throwStoreException(string $filePath, ?\Exception $exception = null): StoreException
    {
        throw new StoreException(
            "It is not possible to store files in path $filePath",
            1002,
            $exception
        );
    }


    private function write(string $content, string $filePath): bool
    {
        $this->logger?->debug("Storing result page to $filePath...");

        $result = @file_put_contents($filePath, $content);

        if ($result !== false) {
            $this->logger?->info("Download finished to ".$this->getResultPagePath());
        } else {
            $this->throwStoreException($filePath);
        }

        return $result;
    }
}
