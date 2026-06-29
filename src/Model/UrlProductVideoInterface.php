<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

interface UrlProductVideoInterface extends ProductVideoInterface
{
    public function getUrl(): ?string;

    public function setUrl(?string $url): void;
}
