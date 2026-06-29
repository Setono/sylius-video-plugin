<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

class UrlProductVideo extends ProductVideo implements UrlProductVideoInterface
{
    protected ?string $url = null;

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }
}
