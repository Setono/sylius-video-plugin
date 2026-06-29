<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Renderer;

use Setono\SyliusVideoPlugin\Exception\UnsupportedVideoException;
use Setono\SyliusVideoPlugin\Filesystem\MediaUrlGeneratorInterface;
use Setono\SyliusVideoPlugin\Model\FileProductVideoInterface;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Twig\Environment;

final class FileProductVideoRenderer implements VideoRendererInterface
{
    public function __construct(
        private readonly Environment $twig,
        private readonly MediaUrlGeneratorInterface $mediaUrlGenerator,
        private readonly string $template = '@SetonoSyliusVideoPlugin/shop/renderer/file.html.twig',
    ) {
    }

    public function supports(ProductVideoInterface $video): bool
    {
        return $video instanceof FileProductVideoInterface;
    }

    public function render(ProductVideoInterface $video): string
    {
        if (!$video instanceof FileProductVideoInterface) {
            throw new UnsupportedVideoException($video);
        }

        $path = $video->getPath();

        return $this->twig->render($this->template, [
            'video' => $video,
            'url' => null === $path ? null : $this->mediaUrlGenerator->generate($path),
        ]);
    }
}
