<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

class EmbedVideo extends ProductVideo implements EmbedVideoInterface
{
    protected ?string $html = null;

    public function getType(): string
    {
        return self::TYPE_EMBED;
    }

    public function getHtml(): ?string
    {
        return $this->html;
    }

    public function setHtml(?string $html): void
    {
        $this->html = $html;
    }
}
