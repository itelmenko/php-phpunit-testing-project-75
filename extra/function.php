<?php

namespace Downloader\Downloader;

use Hexlet\Code\FilePathBuilder;
use Hexlet\Code\Loader;

/**
 * For Hexlet test's needs
 */
if (! function_exists(__NAMESPACE__ .'\downloadPage')) {
    function downloadPage(string $url, ?string $targetPath, string $clientClass): bool
    {
        $targetDir = $targetPath ?? getcwd();

        $loader = new Loader(new $clientClass(), new FilePathBuilder());

        $result = $loader->load($url, $targetDir);

        echo shell_exec("tree $targetDir");

        return $result;
    }
}
