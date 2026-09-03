<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The inverse side of the product/video association, ready to use: an attribute-mapped Product
 * only has to use this trait, since Doctrine reads the attributes below through the using class.
 * An XML- or YAML-mapped Product maps `videos` itself (see the README) and these attributes are
 * simply ignored.
 *
 * @phpstan-require-implements \Sylius\Component\Core\Model\ProductInterface
 * @phpstan-require-implements ProductVideosAwareInterface
 */
trait ProductVideosAwareTrait
{
    /** @var Collection<array-key, ProductVideoInterface> */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductVideo::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
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
