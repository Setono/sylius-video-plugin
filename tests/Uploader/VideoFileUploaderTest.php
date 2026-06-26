<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Uploader;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\Model\EmbedVideo;
use Setono\SyliusVideoPlugin\Model\FileVideo;
use Setono\SyliusVideoPlugin\Uploader\VideoFileUploader;
use Sylius\Component\Core\Filesystem\Adapter\FilesystemAdapterInterface;
use Sylius\Component\Core\Filesystem\Exception\FileNotFoundException;

final class VideoFileUploaderTest extends TestCase
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

        $video = new FileVideo();
        $video->setFile(new \SplFileInfo($this->tmpFile));

        (new VideoFileUploader($filesystem->reveal()))->upload($video);

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

        $video = new EmbedVideo();
        $video->setPosterFile(new \SplFileInfo($this->tmpFile));

        (new VideoFileUploader($filesystem->reveal()))->uploadPoster($video);

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

        $video = new FileVideo();

        (new VideoFileUploader($filesystem->reveal()))->upload($video);

        self::assertNull($video->getPath());
    }

    /**
     * @test
     */
    public function it_removes_the_previously_stored_file_before_re_uploading(): void
    {
        $filesystem = $this->prophesize(FilesystemAdapterInterface::class);
        $filesystem->has('video/old/path.mp4')->willReturn(true);
        $filesystem->delete('video/old/path.mp4')->shouldBeCalledOnce();
        $filesystem->has(Argument::not('video/old/path.mp4'))->willReturn(false);
        $filesystem->write(Argument::type('string'), 'video-bytes')->shouldBeCalledOnce();

        $video = new FileVideo();
        $video->setPath('video/old/path.mp4');
        $video->setFile(new \SplFileInfo($this->tmpFile));

        (new VideoFileUploader($filesystem->reveal()))->upload($video);

        self::assertNotSame('video/old/path.mp4', $video->getPath());
    }

    /**
     * @test
     */
    public function it_returns_true_from_remove_when_the_file_is_deleted(): void
    {
        $filesystem = $this->prophesize(FilesystemAdapterInterface::class);
        $filesystem->delete('video/a/b.mp4')->shouldBeCalledOnce();

        self::assertTrue((new VideoFileUploader($filesystem->reveal()))->remove('video/a/b.mp4'));
    }

    /**
     * @test
     */
    public function it_returns_false_from_remove_when_the_file_is_missing(): void
    {
        $filesystem = $this->prophesize(FilesystemAdapterInterface::class);
        $filesystem->delete('video/a/b.mp4')->willThrow(new FileNotFoundException('video/a/b.mp4'));

        self::assertFalse((new VideoFileUploader($filesystem->reveal()))->remove('video/a/b.mp4'));
    }
}
