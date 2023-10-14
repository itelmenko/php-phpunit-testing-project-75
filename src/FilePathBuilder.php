<?php

namespace Hexlet\Code;

class FilePathBuilder
{

    public function buildFilePath(string $url, ?string $extension = null): string
    {
        $parts = parse_url($url);
        $url = $parts['host'].($parts['path'] ?? '');
        $step1 = preg_replace('/[^0-9A-z]/', '-', $url);
        $step2 = preg_replace('/-+/', '-', $step1);

        return trim($step2, '-').(empty($extension) ? '' : ".$extension");
    }
}
