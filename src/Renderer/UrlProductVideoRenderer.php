<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Renderer;

use Setono\SyliusVideoPlugin\Exception\UnsupportedVideoException;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Setono\SyliusVideoPlugin\Model\UrlProductVideoInterface;
use Twig\Environment;

final class UrlProductVideoRenderer implements VideoRendererInterface
{
    /** @var list<string> */
    private const DIRECT_FILE_EXTENSIONS = ['mp4', 'webm', 'ogg', 'ogv', 'mov', 'm4v'];

    public function __construct(
        private readonly Environment $twig,
        private readonly string $template = '@SetonoSyliusVideoPlugin/shop/renderer/url.html.twig',
    ) {
    }

    public function supports(ProductVideoInterface $video): bool
    {
        return $video instanceof UrlProductVideoInterface;
    }

    public function render(ProductVideoInterface $video, array $context = []): string
    {
        if (!$video instanceof UrlProductVideoInterface) {
            throw new UnsupportedVideoException($video);
        }

        $url = $video->getUrl();

        return $this->twig->render($this->template, array_merge([
            'video' => $video,
            'url' => $url,
            'is_direct_file' => null !== $url && $this->isDirectFile($url),
        ], $context));
    }

    private function isDirectFile(string $url): bool
    {
        $path = parse_url($url, \PHP_URL_PATH);

        if (!is_string($path)) {
            return false;
        }

        $extension = strtolower(pathinfo($path, \PATHINFO_EXTENSION));

        return in_array($extension, self::DIRECT_FILE_EXTENSIONS, true);
    }
}
