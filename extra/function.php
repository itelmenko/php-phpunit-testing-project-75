<?php

/**
 * For Hexlet test's needs
 */

namespace Downloader\Downloader;

use GuzzleHttp\Client;
use Hexlet\Code\FilePathBuilder;
use Hexlet\Code\Loader;

if (! function_exists(__NAMESPACE__ . '\downloadPage')) {
    function downloadPage(string $url, ?string $targetPath, string $clientClass): bool
    {
        /**
         * @var string $targetDir
         */
        $targetDir = $targetPath ?? getcwd();

        /**
         * @var Client $client
         */
        $client = new $clientClass();
        $loader = new Loader($client, new FilePathBuilder());

        return $loader->load($url, $targetDir);
    }
}
