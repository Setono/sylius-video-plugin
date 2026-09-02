<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\EventListener\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\EventListener\Doctrine\ProductVideoFilesRemovalListener;
use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;
use Setono\SyliusVideoPlugin\Model\FileProductVideo;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Setono\SyliusVideoPlugin\Uploader\VideoFileUploaderInterface;

final class ProductVideoFilesRemovalListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_deletes_the_file_and_poster_of_removed_videos_after_the_flush(): void
    {
        $file = new FileProductVideo();
        $file->setPath('video/ab/file.mp4');
        $file->setPosterPath('video/poster/ab/poster.jpg');

        $url = new UrlProductVideo();
        $url->setPosterPath('video/poster/cd/poster.jpg');

        $uploader = $this->prophesize(VideoFileUploaderInterface::class);
        $uploader->remove('video/ab/file.mp4')->willReturn(true)->shouldBeCalledOnce();
        $uploader->remove('video/poster/ab/poster.jpg')->willReturn(true)->shouldBeCalledOnce();
        $uploader->remove('video/poster/cd/poster.jpg')->willReturn(true)->shouldBeCalledOnce();

        $listener = new ProductVideoFilesRemovalListener($uploader->reveal());
        $listener->onFlush($this->onFlush([$file, $url, new EmbedProductVideo(), new \stdClass()]));

        // Nothing is deleted while the transaction may still roll back.
        $uploader->remove(Argument::any())->shouldNotHaveBeenCalled();

        $listener->postFlush($this->postFlush());
    }

    /**
     * @test
     */
    public function it_deletes_a_shared_path_once_and_forgets_it_after_the_flush(): void
    {
        $first = new FileProductVideo();
        $first->setPath('video/ab/same.mp4');
        $second = new FileProductVideo();
        $second->setPath('video/ab/same.mp4');

        $uploader = $this->prophesize(VideoFileUploaderInterface::class);
        $uploader->remove('video/ab/same.mp4')->willReturn(true)->shouldBeCalledOnce();

        $listener = new ProductVideoFilesRemovalListener($uploader->reveal());
        $listener->onFlush($this->onFlush([$first, $second]));
        $listener->postFlush($this->postFlush());
        $listener->postFlush($this->postFlush());
    }

    /**
     * @test
     */
    public function it_ignores_removed_videos_without_stored_files(): void
    {
        $uploader = $this->prophesize(VideoFileUploaderInterface::class);
        $uploader->remove(Argument::any())->shouldNotBeCalled();

        $listener = new ProductVideoFilesRemovalListener($uploader->reveal());
        $listener->onFlush($this->onFlush([new EmbedProductVideo(), new FileProductVideo()]));
        $listener->postFlush($this->postFlush());
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

    private function postFlush(): PostFlushEventArgs
    {
        return new PostFlushEventArgs($this->prophesize(EntityManagerInterface::class)->reveal());
    }
}
