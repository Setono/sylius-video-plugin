<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

interface EmbedVideoInterface extends ProductVideoInterface
{
    public function getHtml(): ?string;

    public function setHtml(?string $html): void;
}
