<?php

/**
 * For Hexlet test's needs
 */

namespace Downloader\Downloader;

use Hexlet\Code\FilePathBuilder;
use Hexlet\Code\Loader;

if (! function_exists(__NAMESPACE__ . '\downloadPage')) {
    function downloadPage(string $url, ?string $targetPath, string $clientClass): bool
    {
        $targetDir = $targetPath ?? getcwd();

        $loader = new Loader(new $clientClass(), new FilePathBuilder());

        return $loader->load($url, $targetDir);
    }
}
