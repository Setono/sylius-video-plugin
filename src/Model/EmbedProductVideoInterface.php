<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

interface EmbedProductVideoInterface extends ProductVideoInterface
{
    public function getHtml(): ?string;

    public function setHtml(?string $html): void;
}
