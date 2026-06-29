<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

use Setono\SyliusVideoPlugin\Kind\AsVideoKind;

#[AsVideoKind(label: 'setono_sylius_video.type.url', field: 'url')]
class UrlProductVideo extends ProductVideo implements UrlProductVideoInterface
{
    protected ?string $url = null;

    public static function getType(): string
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
