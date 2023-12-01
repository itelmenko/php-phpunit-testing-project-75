<?php

namespace Hexlet\Code;

class FilePathBuilder
{
    public function buildFilePath(string $url, bool $keepExtension = true, ?string $defaultExtension = null): string
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? '';
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if (!empty($extension)) {
            $path = $this->removeExtension($extension, $path);
        }

        $url = $parts['host'] . $path;
        $step1 = preg_replace('/[^0-9A-z]/', '-', $url);
        $step2 = trim(preg_replace('/-+/', '-', $step1), '-');

        if (empty($extension)) {
            $extension = $defaultExtension;
        }

        return ($keepExtension and !empty($extension)) ? "{$step2}.{$extension}" : $step2;
    }

    private function removeExtension(string $extension, string $path): string
    {
        $position = strripos($path, ".$extension");

        return $position !== false ? substr_replace($path, '', $position, strlen(".$extension")) : $path;
    }

    public function buildIndexPath(string $url, string $extension = 'html'): string
    {
        return $this->buildFilePath($url, false) . ".$extension";
    }

    public function buildFolderPath(string $url, string $postfix = '_files'): string
    {
        return $this->buildFilePath($url, false) . $postfix;
    }
}
