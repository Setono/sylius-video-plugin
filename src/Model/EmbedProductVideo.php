<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

use Setono\SyliusVideoPlugin\Kind\AsVideoKind;

#[AsVideoKind(label: 'setono_sylius_video.type.embed', field: 'html')]
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
