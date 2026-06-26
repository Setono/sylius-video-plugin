<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\Exception\UnsupportedVideoException;
use Setono\SyliusVideoPlugin\Model\UrlVideo;

final class UnsupportedVideoExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_names_the_unsupported_video_class_in_the_message(): void
    {
        $exception = new UnsupportedVideoException(new UrlVideo());

        self::assertStringContainsString(UrlVideo::class, $exception->getMessage());
    }

    /**
     * @test
     */
    public function it_keeps_the_previous_throwable(): void
    {
        $previous = new \RuntimeException('boom');

        $exception = new UnsupportedVideoException(new UrlVideo(), $previous);

        self::assertSame($previous, $exception->getPrevious());
    }
}
