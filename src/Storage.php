<?php

namespace Hexlet\Code;

use Exception;

class Storage
{

    public function write(string $content, string $filePath): bool
    {
        try {
            $result = file_put_contents($filePath, $content);
        } catch (Exception) {
            return false;
        }

        return $result !== false;
    }
}
