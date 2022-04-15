<?php

namespace CrawlerCoinMarketCap;

class Coin
{
    public string $name = '';
    public string $price = '';
    public float $percent = 0.0;
    public string $mainet = '';
    public string $address = ' ';
    public string $cmcLink = '';


    public function __construct($name, $price, $percent, $link)
    {
        $this->name = $name;
        $this->price = $price;
        $this->percent = (float)$percent;
        $this->cmcLink = 'https://coinmarketcap.com' . $link;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPercent(): float
    {
        return $this->percent;
    }

    public function getMainet(): string
    {
        return $this->mainet;
    }

    public function setMainet(string $mainet): void
    {
        $this->mainet = $mainet;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function getCmcLink(): string
    {
        return $this->cmcLink;
    }

    public function getDescription(): ?string
    {

        $poocoin = str_replace("https://bscscan.com/token/", "https://poocoin.app/tokens/", $this->getAddress());
        return "Name: " . $this->getName() . PHP_EOL .
            "Drop percent: -" . $this->getPercent() . '%' . PHP_EOL .
            "Cmc: " . $this->getCmcLink() . PHP_EOL .
            "Poocoin:  " . $poocoin . PHP_EOL;
    }

}