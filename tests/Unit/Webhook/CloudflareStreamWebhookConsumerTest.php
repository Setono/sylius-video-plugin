<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Webhook;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideo;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideoInterface;
use Setono\SyliusVideoPlugin\Webhook\CloudflareStreamWebhookConsumer;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;

final class CloudflareStreamWebhookConsumerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<RepositoryInterface<CloudflareStreamProductVideoInterface>> */
    private ObjectProphecy $repository;

    /** @var ObjectProphecy<EntityManagerInterface> */
    private ObjectProphecy $manager;

    /** @var ObjectProphecy<LoggerInterface> */
    private ObjectProphecy $logger;

    protected function setUp(): void
    {
        /** @var ObjectProphecy<RepositoryInterface<CloudflareStreamProductVideoInterface>> $repository */
        $repository = $this->prophesize(RepositoryInterface::class);
        $this->repository = $repository;
        $this->manager = $this->prophesize(EntityManagerInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class);
    }

    /**
     * @test
     */
    public function it_marks_the_video_ready_when_cloudflare_reports_it_ready_to_stream(): void
    {
        $video = new CloudflareStreamProductVideo();
        $video->setUid('video123');

        $this->repository->findOneBy(['uid' => 'video123'])->willReturn($video);
        $this->manager->flush()->shouldBeCalledOnce();
        $this->logger->warning(Argument::cetera())->shouldNotBeCalled();

        $this->consumer()->consume($this->event('video123', ['readyToStream' => true, 'status' => ['state' => 'ready']]));

        self::assertTrue($video->isReady());
    }

    /**
     * @test
     */
    public function it_keeps_the_video_pending_and_logs_when_cloudflare_reports_an_error(): void
    {
        $video = new CloudflareStreamProductVideo();
        $video->setUid('video123');
        $video->setReady(true);

        $this->repository->findOneBy(['uid' => 'video123'])->willReturn($video);
        $this->manager->flush()->shouldBeCalledOnce();
        $this->logger->warning(Argument::containingString('could not process video'), Argument::withEntry('reason', 'The file is not a video.'))->shouldBeCalledOnce();

        $this->consumer()->consume($this->event('video123', ['readyToStream' => false, 'status' => ['state' => 'error', 'errorReasonText' => 'The file is not a video.']]));

        self::assertFalse($video->isReady());
    }

    /**
     * @test
     */
    public function it_logs_an_unknown_reason_when_the_error_carries_none(): void
    {
        $video = new CloudflareStreamProductVideo();
        $video->setUid('video123');

        $this->repository->findOneBy(['uid' => 'video123'])->willReturn($video);
        $this->manager->flush()->shouldBeCalledOnce();
        $this->logger->warning(Argument::type('string'), Argument::withEntry('reason', 'unknown reason'))->shouldBeCalledOnce();

        $this->consumer()->consume($this->event('video123', ['status' => ['state' => 'error']]));

        self::assertFalse($video->isReady());
    }

    /**
     * @test
     */
    public function it_only_counts_a_real_boolean_true_as_ready(): void
    {
        $video = new CloudflareStreamProductVideo();
        $video->setUid('video123');

        $this->repository->findOneBy(['uid' => 'video123'])->willReturn($video);
        $this->manager->flush()->shouldBeCalledOnce();

        $this->consumer()->consume($this->event('video123', ['readyToStream' => 'yes']));

        self::assertFalse($video->isReady());
    }

    /**
     * @test
     */
    public function it_ignores_a_notification_for_a_video_it_does_not_know(): void
    {
        $this->repository->findOneBy(['uid' => 'unknown'])->willReturn(null);
        $this->manager->flush()->shouldNotBeCalled();
        $this->logger->info(Argument::containingString('unknown video'), ['uid' => 'unknown'])->shouldBeCalledOnce();

        $this->consumer()->consume($this->event('unknown', ['readyToStream' => true]));
    }

    private function consumer(): CloudflareStreamWebhookConsumer
    {
        $registry = $this->prophesize(ManagerRegistry::class);
        $registry->getManagerForClass(CloudflareStreamProductVideo::class)->willReturn($this->manager->reveal());

        return new CloudflareStreamWebhookConsumer($this->repository->reveal(), $registry->reveal(), $this->logger->reveal());
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function event(string $uid, array $payload): RemoteEvent
    {
        return new RemoteEvent('cloudflare_stream.video', $uid, ['uid' => $uid] + $payload);
    }
}
