<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamClientInterface;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamException;
use Setono\SyliusVideoPlugin\CloudflareStream\ReadinessSynchronizer;
use Setono\SyliusVideoPlugin\CloudflareStream\VideoDetails;
use Setono\SyliusVideoPlugin\Command\CloudflareStreamSyncCommand;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideo;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideoInterface;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CloudflareStreamSyncCommandTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_marks_finished_videos_ready_and_reports_the_rest(): void
    {
        $ready = $this->video('done');
        $pending = $this->video('encoding');
        $broken = $this->video('broken');

        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->getVideo('done')->willReturn(new VideoDetails('done', true, 'ready'));
        $client->getVideo('encoding')->willReturn(new VideoDetails('encoding', false, 'inprogress'));
        $client->getVideo('broken')->willReturn(new VideoDetails('broken', false, 'error', 'The file is not a video.'));

        $tester = $this->tester([$ready, $pending, $broken], $client->reveal(), $this->flushingRegistry());
        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertTrue($ready->isReady());
        self::assertFalse($pending->isReady());
        self::assertFalse($broken->isReady());

        $display = $tester->getDisplay();
        self::assertStringContainsString('done: ready', $display);
        self::assertStringContainsString('encoding: inprogress', $display);
        self::assertStringContainsString('broken: error (The file is not a video.)', $display);
        self::assertStringContainsString('1 video(s) became ready, 2 still processing, 0 could not be checked.', $display);
    }

    /**
     * @test
     */
    public function it_fails_but_carries_on_when_cloudflare_cannot_be_asked_about_a_video(): void
    {
        $failing = $this->video('failing');
        $ready = $this->video('done');

        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->getVideo('failing')->willThrow(new CloudflareStreamException('Authentication error'));
        $client->getVideo('done')->willReturn(new VideoDetails('done', true, 'ready'));

        $tester = $this->tester([$failing, $ready], $client->reveal(), $this->flushingRegistry());
        $exitCode = $tester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertTrue($ready->isReady());
        self::assertStringContainsString('failing: Authentication error', $tester->getDisplay());
        self::assertStringContainsString('1 video(s) became ready, 0 still processing, 1 could not be checked.', $tester->getDisplay());
    }

    /**
     * @test
     */
    public function it_skips_videos_without_a_uid_and_videos_of_other_types(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->getVideo(Argument::any())->shouldNotBeCalled();

        $tester = $this->tester([new CloudflareStreamProductVideo(), new UrlProductVideo()], $client->reveal(), $this->flushingRegistry());

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('0 video(s) became ready, 0 still processing, 0 could not be checked.', $tester->getDisplay());
    }

    /**
     * @test
     */
    public function it_has_a_stable_name(): void
    {
        self::assertSame('setono:sylius-video:cloudflare-stream:sync', CloudflareStreamSyncCommand::getDefaultName());
    }

    private function video(string $uid): CloudflareStreamProductVideo
    {
        $video = new CloudflareStreamProductVideo();
        $video->setUid($uid);

        return $video;
    }

    /**
     * A registry whose manager for the video class expects exactly one flush: the command saves
     * everything it synchronised in one go.
     */
    private function flushingRegistry(): ManagerRegistry
    {
        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->flush()->shouldBeCalledOnce();

        $registry = $this->prophesize(ManagerRegistry::class);
        $registry->getManagerForClass(CloudflareStreamProductVideo::class)->willReturn($manager->reveal());

        return $registry->reveal();
    }

    /**
     * @param list<object> $pending
     */
    private function tester(array $pending, CloudflareStreamClientInterface $client, ManagerRegistry $registry): CommandTester
    {
        /** @var \Prophecy\Prophecy\ObjectProphecy<RepositoryInterface<CloudflareStreamProductVideoInterface>> $repository */
        $repository = $this->prophesize(RepositoryInterface::class);
        $repository->findBy(['ready' => false])->willReturn($pending);
        $repository->getClassName()->willReturn(CloudflareStreamProductVideo::class);

        return new CommandTester(new CloudflareStreamSyncCommand(new ReadinessSynchronizer($client), $repository->reveal(), $registry));
    }
}
