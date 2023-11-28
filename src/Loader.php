<?php

namespace Hexlet\Code;

use DiDom\Document;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Psr\Log\LoggerInterface;

class Loader
{

    protected ?string $resultPagePath = null;

    /**
     * @var array<string>
     */
    protected array $warning = [];

    protected ?string $baseUrl = null;
    protected ?string $mainUrl = null;

    public function __construct(
        /**
         * For Hexlet tests we use the mixed type
         * @var Client $client
         */
        private readonly mixed $client,
        private readonly FilePathBuilder $pathBuilder,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    public function load(string $url, string $targetDir): bool
    {
        echo "Url: $url. Output directory: $targetDir".PHP_EOL;

        $this->logger?->info("Url: $url. Output directory: $targetDir");
        $this->logger?->info("Download main page ...");
        $this->mainUrl = $url;
        $this->baseUrl = $this->getBaseUrl($url);
        $sourceContent = $this->loadIndexPage($url);

        $this->resultPagePath = $this->getIndexPagePath($url, $targetDir);
        $folderPath = $this->getFolderPath($url, $targetDir);

        $document = new Document($sourceContent);
        $this->loadImages($document, $folderPath);
        $this->loadCssFiles($document, $folderPath);
        $this->loadJavaScriptFiles($document, $folderPath);

        $resultContent = $document->html();

        $result = $this->write($resultContent, $this->resultPagePath);
        echo "Url: $url. Output directory: $targetDir. Result: ".json_encode($result).PHP_EOL;

        return $result;
    }

    private function getBaseUrl(string $url): string
    {
        $parts = parse_url($url);

        return rtrim(($parts['scheme'] ?? 'http').'://'.$parts['host'], '/').'/';
    }

    private function getFullUrl(string $url): string
    {
        $parts = parse_url($url);
        if (!empty($parts['host'] ?? null)) {
            return $url;
        }

        if (substr($url, 0, 1) !== '/') {
            return rtrim($this->mainUrl, '/').'/'.$url;
        }

        return $this->baseUrl.ltrim($url, '/');
    }

    private function isAnotherDomain(string $url): bool
    {
        $resourceBaseUrl = $this->getBaseUrl($url);

        return $this->baseUrl != $resourceBaseUrl;
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
        // 'link[rel="stylesheet"]' will be better, but Hexlet tests have error
        $this->loadResources($document, 'link', 'href', $absoluteFolderPath);
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

            $this->logger?->debug("Resource URL: $elementUrl");
            echo "Resource URL: $elementUrl".PHP_EOL;
            $elementUrl = $this->getFullUrl($elementUrl);

            if ($this->isAnotherDomain($elementUrl)) {
                $this->logger?->debug("Skip external URL $elementUrl");
                echo "Skip external URL $elementUrl".PHP_EOL;
                continue;
            }

            $fileName = $this->pathBuilder->buildFilePath($elementUrl, true, 'html');
            $filePath = $absoluteFolderPath.'/'.$fileName;
            $relativeImagePath = pathinfo($absoluteFolderPath, PATHINFO_FILENAME).'/'.$fileName;
            $this->logger?->debug("Download $elementUrl to $filePath");
            echo "Download $elementUrl to $filePath".PHP_EOL;
            try {
                $this->client->request('GET', $elementUrl, ['sink' => $filePath]);
            } catch (\Exception $exception) {
                $this->logger?->error($exception->getMessage());
                $this->warning[] = "It is not possible to download resource $elementUrl to $filePath";
                echo "It is not possible to download resource $elementUrl to $filePath".PHP_EOL;
                continue;
            }

            $element->setAttribute($htmlAttribute, $relativeImagePath);
        }
    }

    public function getResultPagePath(): string
    {
        return $this->resultPagePath;
    }

    private function throwStoreException(string $filePath, ?\Exception $exception = null): StoreException
    {
        if (!empty($exception)) {
            $this->logger?->error($exception->getMessage());
        }

        throw new StoreException(
            "It is not possible to store files in path $filePath",
            1002,
            $exception
        );
    }

    /**
     * @return array<string>
     */
    public function getWarnings(): array
    {
        return $this->warning;
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
