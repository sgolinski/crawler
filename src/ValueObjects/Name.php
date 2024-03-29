<?php

namespace CrawlerCoinMarketCap\ValueObjects;

class Name
{
    private string $name;

    private function __construct(
        string $name
    )
    {
        $name = $this->ensureIsLowerLetter($name);
        $this->name = $name;
    }

    public static function fromString(
        string $name
    ): self
    {
        return new self($name);
    }

    public function asString(): string
    {
        return $this->name;
    }

    public function ensureIsLowerLetter(
        string $str
    ): string
    {
        return trim(strtolower($str));
    }
}