<?php

namespace CrawlerCoinMarketCap\Entity;

use CrawlerCoinMarketCap\ValueObjects\Address;
use CrawlerCoinMarketCap\ValueObjects\Chain;
use CrawlerCoinMarketCap\ValueObjects\DropPercent;
use CrawlerCoinMarketCap\ValueObjects\Name;
use CrawlerCoinMarketCap\ValueObjects\Url;

interface Token
{

    public function getName(): Name;

    public function getPercent(): DropPercent;

    public function getUrl(): Url;

    public function alert(): ?string;

    public function setDropPercent(DropPercent $dropPercent);

    public function setCreated(int $created);

    public function getCreated(): int;

    public function isComplete(): bool;

    public function setAddress(Address $address);

    public function setData(): void;

    public function setChain(Chain $chain);

    public function setPoocoinAddress(Address $address): void;

    public function isProcessed(): bool;

    public function setProcessed(): void;
}