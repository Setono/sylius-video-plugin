<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

class EmbedProductVideo extends ProductVideo implements EmbedProductVideoInterface
{
    protected ?string $html = null;

    public function getHtml(): ?string
    {
        return $this->html;
    }

    public function setHtml(?string $html): void
    {
        $this->html = $html;
    }
}
