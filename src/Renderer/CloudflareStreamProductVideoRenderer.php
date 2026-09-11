<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Renderer;

use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamUrlGeneratorInterface;
use Setono\SyliusVideoPlugin\Exception\UnsupportedVideoException;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideoInterface;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Twig\Environment;

/**
 * Renders a Cloudflare Stream video as a Video.js player fed by Cloudflare's HLS and DASH
 * manifests. The template marks the element with `data-setono-sylius-video-player`, which the
 * plugin's shop script mounts (loading Video.js on demand). A video Cloudflare has not finished
 * processing renders nothing: there is no stream to play yet.
 */
final class CloudflareStreamProductVideoRenderer implements VideoRendererInterface
{
    public function __construct(
        private readonly Environment $twig,
        private readonly CloudflareStreamUrlGeneratorInterface $urlGenerator,
        private readonly string $template = '@SetonoSyliusVideoPlugin/shop/renderer/cloudflare_stream.html.twig',
    ) {
    }

    public function supports(ProductVideoInterface $video): bool
    {
        return $video instanceof CloudflareStreamProductVideoInterface;
    }

    public function render(ProductVideoInterface $video): string
    {
        if (!$video instanceof CloudflareStreamProductVideoInterface) {
            throw new UnsupportedVideoException($video);
        }

        $uid = $video->getUid();

        if (null === $uid || !$video->isReady()) {
            return '';
        }

        return $this->twig->render($this->template, [
            'video' => $video,
            'hls_url' => $this->urlGenerator->hlsManifest($uid),
            'dash_url' => $this->urlGenerator->dashManifest($uid),
        ]);
    }
}
