<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

use Sylius\Component\Core\Model\ProductInterface;

/**
 * Base of every video type. It is abstract: only the concrete subtypes registered as Sylius
 * resources have a discriminator value and can be persisted.
 */
abstract class ProductVideo implements ProductVideoInterface
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

    /**
     * Derives the Single Table Inheritance discriminator value from the class name: the part
     * before the `ProductVideo` suffix, snake-cased (e.g. `UrlProductVideo` => `url`,
     * `EmbedProductVideo` => `embed`). Subtypes only need to override this for a non-conventional
     * name. The base class itself is abstract, has no type and throws.
     */
    public static function getType(): string
    {
        $name = (new \ReflectionClass(static::class))->getShortName();
        $prefix = (string) preg_replace('/ProductVideo$/', '', $name);

        if ('' === $prefix) {
            throw new \LogicException(sprintf(
                'Cannot derive a video type for "%s". Name the subtype "<Type>ProductVideo" or override getType().',
                static::class,
            ));
        }

        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $prefix));
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
