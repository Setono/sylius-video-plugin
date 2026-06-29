<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Renderer;

use Setono\SyliusVideoPlugin\Exception\UnsupportedVideoException;
use Setono\SyliusVideoPlugin\Model\EmbedProductVideoInterface;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Twig\Environment;

final class EmbedProductVideoRenderer implements VideoRendererInterface
{
    public function __construct(
        private readonly Environment $twig,
        private readonly string $template = '@SetonoSyliusVideoPlugin/shop/renderer/embed.html.twig',
    ) {
    }

    public function supports(ProductVideoInterface $video): bool
    {
        return $video instanceof EmbedProductVideoInterface;
    }

    public function render(ProductVideoInterface $video): string
    {
        if (!$video instanceof EmbedProductVideoInterface) {
            throw new UnsupportedVideoException($video);
        }

        return $this->twig->render($this->template, [
            'video' => $video,
            'html' => $video->getHtml(),
        ]);
    }
}
