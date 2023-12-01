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

        return realpath(implode('/', $parts)) ?: '/';
    }

    public function getFixture(string $fixtureName): string
    {
        return file_get_contents($this->getFixtureFullPath($fixtureName)) ?: '';
    }
}
