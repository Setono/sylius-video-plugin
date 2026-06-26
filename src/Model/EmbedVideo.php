<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

class EmbedVideo extends ProductVideo implements EmbedVideoInterface
{
    protected ?string $code = null;

    public function getType(): string
    {
        return self::TYPE_EMBED;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }
}
