<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Renderer;

use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;

interface VideoRendererInterface
{
    public function supports(ProductVideoInterface $video): bool;

    /**
     * @param array<string, mixed> $context
     */
    public function render(ProductVideoInterface $video, array $context = []): string;
}
