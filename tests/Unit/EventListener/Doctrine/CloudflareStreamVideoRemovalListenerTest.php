<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamClientInterface;
use Setono\SyliusVideoPlugin\CloudflareStream\CloudflareStreamException;
use Setono\SyliusVideoPlugin\EventListener\Doctrine\CloudflareStreamVideoRemovalListener;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideo;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;

final class CloudflareStreamVideoRemovalListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_deletes_removed_videos_from_cloudflare_after_the_flush(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->deleteVideo('video1')->shouldBeCalledOnce();
        $client->deleteVideo('video2')->shouldBeCalledOnce();

        $listener = new CloudflareStreamVideoRemovalListener($client->reveal());
        $listener->onFlush($this->onFlush([$this->video('video1'), $this->video('video2'), new UrlProductVideo(), new \stdClass()]));

        // Nothing is deleted while the transaction may still roll back.
        $client->deleteVideo(Argument::any())->shouldNotHaveBeenCalled();

        $listener->postFlush($this->postFlush());
    }

    /**
     * @test
     */
    public function it_deletes_the_previous_video_when_a_saved_row_is_given_a_new_uid(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->deleteVideo('old')->shouldBeCalledOnce();
        $client->deleteVideo('new')->shouldNotBeCalled();

        $listener = new CloudflareStreamVideoRemovalListener($client->reveal());
        $listener->preUpdate($this->preUpdate($this->video('new'), ['uid' => ['old', 'new']]));
        $listener->postFlush($this->postFlush());
    }

    /**
     * @test
     */
    public function it_ignores_updates_that_keep_the_uid_or_set_the_first_one(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->deleteVideo(Argument::any())->shouldNotBeCalled();

        $listener = new CloudflareStreamVideoRemovalListener($client->reveal());
        $listener->preUpdate($this->preUpdate($this->video('same'), ['ready' => [false, true]]));
        $listener->preUpdate($this->preUpdate($this->video('first'), ['uid' => [null, 'first']]));
        $listener->preUpdate($this->preUpdate(new UrlProductVideo(), ['url' => ['a', 'b']]));
        $listener->postFlush($this->postFlush());
    }

    /**
     * @test
     */
    public function it_deletes_a_uid_once_and_forgets_it_after_the_flush(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->deleteVideo('video1')->shouldBeCalledOnce();

        $listener = new CloudflareStreamVideoRemovalListener($client->reveal());
        $listener->onFlush($this->onFlush([$this->video('video1'), $this->video('video1')]));
        $listener->postFlush($this->postFlush());
        $listener->postFlush($this->postFlush());
    }

    /**
     * @test
     */
    public function it_ignores_removed_videos_without_a_uid(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->deleteVideo(Argument::any())->shouldNotBeCalled();

        $listener = new CloudflareStreamVideoRemovalListener($client->reveal());
        $listener->onFlush($this->onFlush([new CloudflareStreamProductVideo(), $this->video('')]));
        $listener->postFlush($this->postFlush());
    }

    /**
     * @test
     */
    public function it_logs_instead_of_throwing_when_cloudflare_refuses_the_deletion(): void
    {
        $client = $this->prophesize(CloudflareStreamClientInterface::class);
        $client->deleteVideo('video1')->willThrow(new CloudflareStreamException('Authentication error'));
        $client->deleteVideo('video2')->shouldBeCalledOnce();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error(Argument::containingString('Could not delete video'), Argument::withEntry('uid', 'video1'))->shouldBeCalledOnce();

        $listener = new CloudflareStreamVideoRemovalListener($client->reveal(), $logger->reveal());
        $listener->onFlush($this->onFlush([$this->video('video1'), $this->video('video2')]));
        $listener->postFlush($this->postFlush());
    }

    private function video(string $uid): CloudflareStreamProductVideo
    {
        $video = new CloudflareStreamProductVideo();
        $video->setUid($uid);

        return $video;
    }

    /**
     * @param list<object> $deletions
     */
    private function onFlush(array $deletions): OnFlushEventArgs
    {
        $unitOfWork = $this->prophesize(UnitOfWork::class);
        $unitOfWork->getScheduledEntityDeletions()->willReturn($deletions);

        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->getUnitOfWork()->willReturn($unitOfWork->reveal());

        return new OnFlushEventArgs($manager->reveal());
    }

    /**
     * @param array<string, array{mixed, mixed}> $changeSet
     */
    private function preUpdate(object $entity, array $changeSet): PreUpdateEventArgs
    {
        return new PreUpdateEventArgs($entity, $this->prophesize(EntityManagerInterface::class)->reveal(), $changeSet);
    }

    private function postFlush(): PostFlushEventArgs
    {
        return new PostFlushEventArgs($this->prophesize(EntityManagerInterface::class)->reveal());
    }
}
