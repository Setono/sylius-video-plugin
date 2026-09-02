<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Application\Entity\Video;

use Setono\SyliusVideoPlugin\Model\UrlProductVideo;

/**
 * The README's worked example of an application-defined type: extends the URL type to reuse its
 * `url` column and accessors, derives the discriminator `youtube` from its class name, and parses
 * the YouTube id for its own renderer and poster resolver.
 */
class YoutubeProductVideo extends UrlProductVideo implements YoutubeProductVideoInterface
{
    public function getVideoId(): ?string
    {
        $url = (string) $this->getUrl();

        if (1 === preg_match('#youtu\.be/([\w-]{11})#', $url, $matches)) {
            return $matches[1];
        }

        parse_str((string) parse_url($url, \PHP_URL_QUERY), $query);
        $id = $query['v'] ?? null;

        return is_string($id) && '' !== $id ? $id : null;
    }
}
