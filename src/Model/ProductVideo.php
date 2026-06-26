<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

use Sylius\Component\Core\Model\ProductInterface;

class ProductVideo implements ProductVideoInterface
{
    protected ?int $id = null;

    protected ?ProductInterface $product = null;

    protected ?int $position = null;

    protected ?string $posterPath = null;

    protected ?\SplFileInfo $posterFile = null;

    protected ?\DateTimeImmutable $createdAt = null;

    protected ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public static function getType(): string
    {
        // The base class is abstract by convention (Sylius resource bundle limitation); real
        // instances are always FileVideo, UrlVideo or EmbedVideo, all of which override this.
        throw new \LogicException(sprintf(
            'Video type is not defined for %s. Subclasses must override getType().',
            static::class,
        ));
    }

    public function getProduct(): ?ProductInterface
    {
        return $this->product;
    }

    public function setProduct(?ProductInterface $product): void
    {
        $this->product = $product;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    public function getPosterPath(): ?string
    {
        return $this->posterPath;
    }

    public function setPosterPath(?string $posterPath): void
    {
        $this->posterPath = $posterPath;
    }

    public function getPosterFile(): ?\SplFileInfo
    {
        return $this->posterFile;
    }

    public function setPosterFile(?\SplFileInfo $posterFile): void
    {
        $this->posterFile = $posterFile;
    }

    public function hasPosterFile(): bool
    {
        return null !== $this->posterFile;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
