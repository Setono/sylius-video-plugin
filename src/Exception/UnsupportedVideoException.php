<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Exception;

use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;

final class UnsupportedVideoException extends \RuntimeException
{
    public function __construct(ProductVideoInterface $video, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('No service supports the video "%s".', $video::class), 0, $previous);
    }
}
