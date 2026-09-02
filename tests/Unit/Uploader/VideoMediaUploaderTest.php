<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Uploader;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;
use Setono\SyliusVideoPlugin\Model\FileProductVideo;
use Setono\SyliusVideoPlugin\Uploader\VideoMediaUploader;
use Sylius\Component\Core\Filesystem\Adapter\FilesystemAdapterInterface;
use Sylius\Component\Core\Filesystem\Exception\FileNotFoundException;

final class VideoMediaUploaderTest extends TestCase
{
    use ProphecyTrait;

    private string $tmpFile = '';

    protected function setUp(): void
    {
        $this->tmpFile = (string) tempnam(sys_get_temp_dir(), 'setono_video_test');
        file_put_contents($this->tmpFile, 'video-bytes');
    }

    protected function tearDown(): void
    {
        if ('' !== $this->tmpFile && is_file($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    /**
     * @test
     */
    public function it_stores_a_video_file_under_the_video_prefix_and_writes_the_path_back(): void
    {
        $filesystem = $this->prophesize(FilesystemAdapterInterface::class);
        $filesystem->has(Argument::type('string'))->willReturn(false);
        $filesystem->write(Argument::type('string'), 'video-bytes')->shouldBeCalledOnce();

        $video = new FileProductVideo();
        $video->setFile(new \SplFileInfo($this->tmpFile));

        (new VideoMediaUploader($filesystem->reveal()))->upload($video);

        self::assertNotNull($video->getPath());
        self::assertStringStartsWith('video/', $video->getPath());
    }

    /**
     * @test
     */
    public function it_stores_a_poster_under_the_poster_prefix_for_any_video(): void
    {
        $filesystem = $this->prophesize(FilesystemAdapterInterface::class);
        $filesystem->has(Argument::type('string'))->willReturn(false);
        $filesystem->write(Argument::type('string'), 'video-bytes')->shouldBeCalledOnce();

        $video = new EmbedProductVideo();
        $video->setPosterFile(new \SplFileInfo($this->tmpFile));

        (new VideoMediaUploader($filesystem->reveal()))->uploadPoster($video);

        self::assertNotNull($video->getPosterPath());
        self::assertStringStartsWith('video/poster/', $video->getPosterPath());
    }

    /**
     * @test
     */
    public function it_does_nothing_when_there_is_no_pending_file(): void
    {
        $filesystem = $this->prophesize(FilesystemAdapterInterface::class);
        $filesystem->write(Argument::cetera())->shouldNotBeCalled();

        $video = new FileProductVideo();

        (new VideoMediaUploader($filesystem->reveal()))->upload($video);

        self::assertNull($video->getPath());
    }

    /**
     * @test
     */
    public function it_removes_the_previously_stored_file_only_after_the_replacement_is_written(): void
    {
        $calls = [];
        $filesystem = $this->prophesize(FilesystemAdapterInterface::class);
        $filesystem->has('video/old/path.mp4')->willReturn(true);
        $filesystem->has(Argument::not('video/old/path.mp4'))->willReturn(false);
        $filesystem->write(Argument::type('string'), 'video-bytes')->will(function () use (&$calls): void {
            $calls[] = 'write';
        });
        $filesystem->delete('video/old/path.mp4')->will(function () use (&$calls): void {
            $calls[] = 'delete';
        });

        $video = new FileProductVideo();
        $video->setPath('video/old/path.mp4');
        $video->setFile(new \SplFileInfo($this->tmpFile));

        (new VideoMediaUploader($filesystem->reveal()))->upload($video);

        self::assertSame(['write', 'delete'], $calls);
        self::assertNotSame('video/old/path.mp4', $video->getPath());
    }

    /**
     * @test
     */
    public function it_keeps_the_current_file_when_storing_the_replacement_fails(): void
    {
        $filesystem = $this->prophesize(FilesystemAdapterInterface::class);
        $filesystem->has(Argument::type('string'))->willReturn(false);
        $filesystem->write(Argument::type('string'), 'video-bytes')->willThrow(new \RuntimeException('disk full'));
        $filesystem->delete(Argument::any())->shouldNotBeCalled();

        $video = new FileProductVideo();
        $video->setPath('video/old/path.mp4');
        $video->setFile(new \SplFileInfo($this->tmpFile));

        try {
            (new VideoMediaUploader($filesystem->reveal()))->upload($video);
            self::fail('Expected the storage failure to propagate.');
        } catch (\RuntimeException $e) {
            self::assertSame('disk full', $e->getMessage());
        }

        self::assertSame('video/old/path.mp4', $video->getPath());
    }

    /**
     * @test
     */
    public function it_keeps_the_current_poster_when_storing_the_replacement_fails(): void
    {
        $filesystem = $this->prophesize(FilesystemAdapterInterface::class);
        $filesystem->has(Argument::type('string'))->willReturn(false);
        $filesystem->write(Argument::type('string'), 'video-bytes')->willThrow(new \RuntimeException('disk full'));
        $filesystem->delete(Argument::any())->shouldNotBeCalled();

        $video = new EmbedProductVideo();
        $video->setPosterPath('video/poster/old.jpg');
        $video->setPosterFile(new \SplFileInfo($this->tmpFile));

        try {
            (new VideoMediaUploader($filesystem->reveal()))->uploadPoster($video);
            self::fail('Expected the storage failure to propagate.');
        } catch (\RuntimeException) {
        }

        self::assertSame('video/poster/old.jpg', $video->getPosterPath());
    }

    /**
     * @test
     */
    public function it_returns_true_from_remove_when_the_file_is_deleted(): void
    {
        $filesystem = $this->prophesize(FilesystemAdapterInterface::class);
        $filesystem->delete('video/a/b.mp4')->shouldBeCalledOnce();

        self::assertTrue((new VideoMediaUploader($filesystem->reveal()))->remove('video/a/b.mp4'));
    }

    /**
     * @test
     */
    public function it_returns_false_from_remove_when_the_file_is_missing(): void
    {
        $filesystem = $this->prophesize(FilesystemAdapterInterface::class);
        $filesystem->delete('video/a/b.mp4')->willThrow(new FileNotFoundException('video/a/b.mp4'));

        self::assertFalse((new VideoMediaUploader($filesystem->reveal()))->remove('video/a/b.mp4'));
    }
}
