<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Form\Extension\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;

/**
 * Stands in for a Product: a `videos` collection with the adder/remover the property accessor
 * uses when the form maps the collection back (`by_reference: false`).
 */
final class VideosOwner
{
    /** @var Collection<array-key, ProductVideoInterface> */
    private readonly Collection $videos;

    public function __construct()
    {
        $this->videos = new ArrayCollection();
    }

    /**
     * @return Collection<array-key, ProductVideoInterface>
     */
    public function getVideos(): Collection
    {
        return $this->videos;
    }

    public function addVideo(ProductVideoInterface $video): void
    {
        if (!$this->videos->contains($video)) {
            $this->videos->add($video);
        }
    }

    public function removeVideo(ProductVideoInterface $video): void
    {
        $this->videos->removeElement($video);
    }
}
