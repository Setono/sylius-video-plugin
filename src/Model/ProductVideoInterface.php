<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

interface ProductVideoInterface extends ResourceInterface
{
    public const TYPE_FILE = 'file';

    public const TYPE_URL = 'url';

    public const TYPE_EMBED = 'embed';

    /**
     * Returns the Single Table Inheritance discriminator value for this video (one of the
     * TYPE_* constants). The base class throws; every concrete subtype overrides it.
     */
    public function getType(): string;

    public function getProduct(): ?ProductInterface;

    public function setProduct(?ProductInterface $product): void;

    public function getPosition(): ?int;

    public function setPosition(?int $position): void;

    /**
     * Path of the stored poster image on the media filesystem (kind-agnostic, optional).
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
