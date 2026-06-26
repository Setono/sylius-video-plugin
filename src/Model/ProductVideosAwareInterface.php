<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

use Doctrine\Common\Collections\Collection;

/**
 * Implemented by the application's Product entity (together with {@see ProductVideosAwareTrait})
 * to own the inverse side of the ProductVideo association.
 */
interface ProductVideosAwareInterface
{
    /**
     * @return Collection<array-key, ProductVideoInterface>
     */
    public function getVideos(): Collection;

    public function hasVideos(): bool;

    public function hasVideo(ProductVideoInterface $video): bool;

    public function addVideo(ProductVideoInterface $video): void;

    public function removeVideo(ProductVideoInterface $video): void;
}
