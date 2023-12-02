<?php

namespace Hexlet\Code\Tests;

class FixturesService
{
    public function __construct(private string $namespace)
    {
    }

    public function getFixtureFullPath(string $fixtureName): string
    {
        $parts = [__DIR__, "fixtures/{$this->namespace}", $fixtureName];
        $realPath = realpath(implode('/', $parts));

        return  $realPath !== false ? $realPath : '/';
    }

    public function getFixture(string $fixtureName): string
    {
        $content = file_get_contents($this->getFixtureFullPath($fixtureName));

        return  $content !== false ? $content : '';
    }
}
