<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Renderer;

use Setono\SyliusVideoPlugin\Exception\UnsupportedVideoException;
use Setono\SyliusVideoPlugin\Model\EmbedVideoInterface;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Setono\SyliusVideoPlugin\Sanitizer\EmbedSanitizerInterface;
use Twig\Environment;

final class EmbedVideoRenderer implements VideoRendererInterface
{
    public function __construct(
        private readonly Environment $twig,
        private readonly EmbedSanitizerInterface $sanitizer,
        private readonly string $template = '@SetonoSyliusVideoPlugin/shop/renderer/embed.html.twig',
    ) {
    }

    public function supports(ProductVideoInterface $video): bool
    {
        return $video instanceof EmbedVideoInterface;
    }

    public function render(ProductVideoInterface $video, array $context = []): string
    {
        if (!$video instanceof EmbedVideoInterface) {
            throw new UnsupportedVideoException($video);
        }

        $code = $video->getCode();

        return $this->twig->render($this->template, array_merge([
            'video' => $video,
            'code' => null === $code ? '' : $this->sanitizer->sanitize($code),
        ], $context));
    }
}
