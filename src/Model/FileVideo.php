<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

class FileVideo extends ProductVideo implements FileVideoInterface
{
    protected ?string $path = null;

    protected ?\SplFileInfo $file = null;

    public function getType(): string
    {
        return self::TYPE_FILE;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    public function getFile(): ?\SplFileInfo
    {
        return $this->file;
    }

    public function setFile(?\SplFileInfo $file): void
    {
        $this->file = $file;
    }

    public function hasFile(): bool
    {
        return null !== $this->file;
    }
}
