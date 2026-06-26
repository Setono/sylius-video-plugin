<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

class UrlVideo extends ProductVideo implements UrlVideoInterface
{
    protected ?string $url = null;

    public function getType(): string
    {
        return self::TYPE_URL;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }
}
