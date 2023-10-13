<?php

namespace Hexlet\Code;

use GuzzleHttp\Client;

class Loader
{

    private ?string $content = null;

    public function __construct(private readonly Client $client)
    {
    }

    public function load(string $url): void
    {
        $this->content = $this->client->get($url)->getBody()->getContents();
    }

    public function getPageContent(): ?string
    {
        return $this->content;
    }
}
