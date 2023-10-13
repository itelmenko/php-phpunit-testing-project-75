<?php

namespace Hexlet\Code;

class Storage
{

    public function write(string $content, string $filePath): void
    {
        file_put_contents($filePath, $content);
    }
}
