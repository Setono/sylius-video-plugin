<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Uploader;

use Setono\SyliusVideoPlugin\Model\FileVideoInterface;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;

interface VideoFileUploaderInterface
{
    /**
     * Stores a pending video file (FileVideo::getFile()) on the media filesystem and writes the
     * resulting path back onto the video.
     */
    public function upload(FileVideoInterface $video): void;

    /**
     * Stores a pending poster image (ProductVideo::getPosterFile()) for any kind of video and
     * writes the resulting path back onto the video.
     */
    public function uploadPoster(ProductVideoInterface $video): void;

    public function remove(string $path): bool;
}
