<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\CloudflareStream;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamClientInterface;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamException;
use Setono\SyliusVideoPlugin\CloudflareStream\ReadinessSynchronizer;
use Setono\SyliusVideoPlugin\CloudflareStream\VideoDetails;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideo;

final class ReadinessSynchronizerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_marks_a_video_ready_when_cloudflare_reports_it_ready_to_stream(): void
    {
        $details = new VideoDetails('video123', true, 'ready');

        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->getVideo('video123')->willReturn($details);

        $video = new CloudflareStreamProductVideo();
        $video->setUid('video123');

        self::assertSame($details, (new ReadinessSynchronizer($client->reveal()))->sync($video));
        self::assertTrue($video->isReady());
    }

    /**
     * @test
     */
    public function it_keeps_a_video_pending_while_cloudflare_is_still_processing_it(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->getVideo('video123')->willReturn(new VideoDetails('video123', false, 'inprogress'));

        $video = new CloudflareStreamProductVideo();
        $video->setUid('video123');
        $video->setReady(true);

        (new ReadinessSynchronizer($client->reveal()))->sync($video);

        self::assertFalse($video->isReady());
    }

    /**
     * @test
     */
    public function it_refuses_a_video_without_a_uid(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->getVideo(Argument::any())->shouldNotBeCalled();

        $this->expectException(CloudflareStreamException::class);
        $this->expectExceptionMessage('no Cloudflare Stream uid');

        (new ReadinessSynchronizer($client->reveal()))->sync(new CloudflareStreamProductVideo());
    }
}
