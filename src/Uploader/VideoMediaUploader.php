<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Uploader;

use Setono\SyliusVideoPlugin\Model\FileProductVideoInterface;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Sylius\Component\Core\Filesystem\Adapter\FilesystemAdapterInterface;
use Sylius\Component\Core\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Stores the media a video carries — the uploaded file of a file video and the optional poster of
 * any video — on the media filesystem Sylius configures, modelled on
 * {@see \Sylius\Component\Core\Uploader\ImageUploader}. Files are stored under the `video/` prefix
 * and posters under `video/poster/`.
 */
final class VideoMediaUploader implements VideoMediaUploaderInterface
{
    public function __construct(
        private readonly FilesystemAdapterInterface $filesystem,
        private readonly string $pathPrefix = 'video',
        private readonly string $posterPathPrefix = 'video/poster',
    ) {
    }

    public function upload(FileProductVideoInterface $video): void
    {
        if (!$video->hasFile()) {
            return;
        }

        $file = $video->getFile();

        if (null === $file) {
            return;
        }

        // Store the replacement first so a failed write leaves the current file (and path) intact.
        $previousPath = $video->getPath();
        $video->setPath($this->store($this->pathPrefix, $file));
        $this->removeIfStored($previousPath);
    }

    public function uploadPoster(ProductVideoInterface $video): void
    {
        if (!$video->hasPosterFile()) {
            return;
        }

        $file = $video->getPosterFile();

        if (null === $file) {
            return;
        }

        $previousPath = $video->getPosterPath();
        $video->setPosterPath($this->store($this->posterPathPrefix, $file));
        $this->removeIfStored($previousPath);
    }

    public function remove(string $path): bool
    {
        try {
            $this->filesystem->delete($path);
        } catch (FileNotFoundException) {
            return false;
        }

        return true;
    }

    private function removeIfStored(?string $path): void
    {
        if (null !== $path && $this->filesystem->has($path)) {
            $this->remove($path);
        }
    }

    private function store(string $prefix, \SplFileInfo $file): string
    {
        $content = file_get_contents($file->getPathname());

        if (false === $content) {
            throw new \RuntimeException(sprintf('Could not read uploaded file "%s".', $file->getPathname()));
        }

        do {
            $path = $this->generatePath($prefix, $file);
        } while ($this->filesystem->has($path));

        $this->filesystem->write($path, $content);

        return $path;
    }

    private function generatePath(string $prefix, \SplFileInfo $file): string
    {
        $name = bin2hex(random_bytes(16));
        $extension = $this->resolveExtension($file);

        return sprintf(
            '%s/%s/%s%s',
            trim($prefix, '/'),
            substr($name, 0, 2),
            $name,
            '' === $extension ? '' : '.' . $extension,
        );
    }

    private function resolveExtension(\SplFileInfo $file): string
    {
        if ($file instanceof File) {
            $guessed = $file->guessExtension();

            if (null !== $guessed && '' !== $guessed) {
                return $guessed;
            }
        }

        return $file->getExtension();
    }
}
