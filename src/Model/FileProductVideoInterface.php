<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

interface FileProductVideoInterface extends ProductVideoInterface
{
    /**
     * Path of the stored video file on the media filesystem.
     */
    public function getPath(): ?string;

    public function setPath(?string $path): void;

    /**
     * Non-mapped upload carrier for a pending video file (mirrors ImageInterface::getFile()).
     */
    public function getFile(): ?\SplFileInfo;

    public function setFile(?\SplFileInfo $file): void;

    public function hasFile(): bool;
}
