<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Application\Entity\Video;

use Doctrine\ORM\Mapping as ORM;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;

/**
 * The README's worked example of an application-defined type: extends the URL type to reuse its
 * `url` column and accessors, derives the discriminator `youtube` from its class name, and parses
 * the YouTube id for its own renderer and poster resolver. Every class in the discriminator map
 * needs a mapping even when it adds no column; Sylius turns the mapped superclass of a resource
 * into an entity.
 */
#[ORM\MappedSuperclass]
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
