<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Application\Entity\Video;

use Setono\SyliusVideoPlugin\Model\UrlProductVideoInterface;

interface YoutubeProductVideoInterface extends UrlProductVideoInterface
{
    public function getVideoId(): ?string;
}
