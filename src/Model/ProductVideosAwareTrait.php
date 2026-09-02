<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @phpstan-require-implements \Sylius\Component\Core\Model\ProductInterface
 * @phpstan-require-implements ProductVideosAwareInterface
 */
trait ProductVideosAwareTrait
{
    /** @var Collection<array-key, ProductVideoInterface> */
    protected Collection $videos;

    /**
     * @return Collection<array-key, ProductVideoInterface>
     */
    public function getVideos(): Collection
    {
        $this->videos ??= new ArrayCollection();

        return $this->videos;
    }

    public function hasVideos(): bool
    {
        return !$this->getVideos()->isEmpty();
    }

    public function hasVideo(ProductVideoInterface $video): bool
    {
        return $this->getVideos()->contains($video);
    }

    public function addVideo(ProductVideoInterface $video): void
    {
        if (!$this->hasVideo($video)) {
            $video->setProduct($this);
            $this->getVideos()->add($video);
        }
    }

    public function removeVideo(ProductVideoInterface $video): void
    {
        if ($this->hasVideo($video)) {
            $this->getVideos()->removeElement($video);
            $video->setProduct(null);
        }
    }
}
