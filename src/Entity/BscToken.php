<?php

namespace CrawlerCoinMarketCap\Entity;

use CrawlerCoinMarketCap\ValueObjects\Address;
use CrawlerCoinMarketCap\ValueObjects\Chain;
use CrawlerCoinMarketCap\ValueObjects\DropPercent;
use CrawlerCoinMarketCap\ValueObjects\Name;
use CrawlerCoinMarketCap\ValueObjects\Price;
use CrawlerCoinMarketCap\ValueObjects\Url;

class BscToken implements Token
{
    public Name $name;
    public Price $price;
    public DropPercent $percent;
    public ?Chain $chain;
    public ?Address $address;
    public Url $url;
    public int $created;
    public string $poocoinAddress;

    public function __construct(
        Name        $name, Price $price,
        DropPercent $percent,
        Url         $url,
        Address     $address,
        int         $created,
        Chain       $chain
    )
    {
        $this->name = $name;
        $this->price = $price;
        $this->percent = $percent;
        $this->url = $url;
        $this->address = null;
        $this->created = $created;
        $this->chain = null;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function getPercent(): DropPercent
    {
        return $this->percent;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    public function alert(): ?string
    {

        return "Name: " . $this->getName()->asString() . PHP_EOL .
            "Drop percent: -" . $this->getPercent()->asFloat() . '%' . PHP_EOL .
            "Cmc: " . $this->getUrl()->asString() . PHP_EOL .
            "Poocoin:  " . $this->getPoocoinAddress() . PHP_EOL;
    }

    public function setDropPercent(DropPercent $dropPercent)
    {
        $this->percent = $dropPercent;
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
        $this->poocoinAddress = str_replace("https://bscscan.com/token/", "https://poocoin.app/tokens/", $address->asString());
    }

    public function setChain(Chain $chain): void
    {
        $this->chain = $chain;
    }

    public function setCreated(int $created): void
    {
        $this->created = $created;
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    public function getPrice(): Price
    {
        return $this->price;
    }

    public function getPoocoinAddress(): string
    {
        return $this->poocoinAddress;
    }

    public function getChain(): ?Chain
    {
        return $this->chain;
    }

}