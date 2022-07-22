<?php

namespace CrawlerCoinMarketCap;

use CrawlerCoinMarketCap\ValueObjects\Address;
use CrawlerCoinMarketCap\ValueObjects\Chain;
use CrawlerCoinMarketCap\ValueObjects\DropPercent;
use CrawlerCoinMarketCap\ValueObjects\Name;
use CrawlerCoinMarketCap\ValueObjects\Price;
use CrawlerCoinMarketCap\ValueObjects\Url;

class CmcToken
{
    public Name $name;
    public Price $price;
    public DropPercent $percent;
    public Chain $mainet;
    public Address $address;
    public Url $url;


    public function __construct(Name $name, Price $price, DropPercent $percent, Url $url)
    {
        $this->name = $name;
        $this->price = $price;
        $this->percent = $percent;
        $this->url = $url;
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

    public function getDescription(): ?string
    {

        $poocoin = str_replace("https://bscscan.com/token/", "https://poocoin.app/tokens/", $this->getAddress());
        return "Name: " . $this->getName()->asString() . PHP_EOL .
            "Drop percent: -" . $this->getPercent()->asFloat() . '%' . PHP_EOL .
            "Cmc: " . $this->getUrl()->asString() . PHP_EOL .
            "Poocoin:  " . $poocoin . PHP_EOL;
    }

}