<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

interface ProductVideoInterface extends ResourceInterface
{
    /**
     * Returns the Single Table Inheritance discriminator value for this video (e.g. `url`). The
     * base class throws; ProductVideo derives it from the class name and subtypes may override.
     *
     * It is static because the type is a class-level fact, letting callers (the discriminator
     * map listener, the type compiler pass) resolve it from the class without instantiating.
     */
    public static function getType(): string;

    public function getProduct(): ?ProductInterface;

    public function setProduct(?ProductInterface $product): void;

    public function getPosition(): ?int;

    public function setPosition(?int $position): void;

    /**
     * Path of the stored poster image on the media filesystem (type-agnostic, optional).
     */
    public function getPosterPath(): ?string;

    public function setPosterPath(?string $posterPath): void;

    /**
     * Non-mapped upload carrier for a pending poster image (mirrors ImageInterface::getFile()).
     */
    public function getPosterFile(): ?\SplFileInfo;

    public function setPosterFile(?\SplFileInfo $posterFile): void;

    public function hasPosterFile(): bool;

    public function getCreatedAt(): ?\DateTimeImmutable;

    public function getUpdatedAt(): ?\DateTimeImmutable;
}
