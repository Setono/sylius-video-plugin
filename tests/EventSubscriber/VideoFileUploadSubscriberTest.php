<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\EventSubscriber\VideoFileUploadSubscriber;
use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;
use Setono\SyliusVideoPlugin\Model\FileProductVideo;
use Setono\SyliusVideoPlugin\Tests\Application\Entity\Product;
use Setono\SyliusVideoPlugin\Uploader\VideoFileUploaderInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class VideoFileUploadSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_subscribes_to_the_product_pre_create_and_pre_update_events(): void
    {
        self::assertSame([
            'sylius.product.pre_create' => 'upload',
            'sylius.product.pre_update' => 'upload',
        ], VideoFileUploadSubscriber::getSubscribedEvents());
    }

    /**
     * @test
     */
    public function it_uploads_a_pending_file_video(): void
    {
        $video = new FileProductVideo();
        $video->setFile(new \SplFileInfo(__FILE__));

        $product = new Product();
        $product->addVideo($video);

        $uploader = $this->prophesize(VideoFileUploaderInterface::class);
        $uploader->upload($video)->shouldBeCalledOnce()->will(static function () use ($video): void {
            $video->setPath('video/a/b.mp4');
        });
        $uploader->uploadPoster(Argument::cetera())->shouldNotBeCalled();

        (new VideoFileUploadSubscriber($uploader->reveal()))->upload(new GenericEvent($product));

        self::assertTrue($product->hasVideo($video));
    }

    /**
     * @test
     */
    public function it_uploads_a_pending_poster_for_any_video_kind(): void
    {
        $video = new EmbedProductVideo();
        $video->setPosterFile(new \SplFileInfo(__FILE__));

        $product = new Product();
        $product->addVideo($video);

        $uploader = $this->prophesize(VideoFileUploaderInterface::class);
        $uploader->uploadPoster($video)->shouldBeCalledOnce();

        (new VideoFileUploadSubscriber($uploader->reveal()))->upload(new GenericEvent($product));
    }

    /**
     * @test
     */
    public function it_removes_a_file_video_whose_upload_produced_no_path(): void
    {
        $video = new FileProductVideo();
        $video->setFile(new \SplFileInfo(__FILE__));

        $product = new Product();
        $product->addVideo($video);

        $uploader = $this->prophesize(VideoFileUploaderInterface::class);
        $uploader->upload($video)->shouldBeCalledOnce();

        (new VideoFileUploadSubscriber($uploader->reveal()))->upload(new GenericEvent($product));

        self::assertFalse($product->hasVideo($video));
    }

    /**
     * @test
     */
    public function it_ignores_subjects_that_are_not_video_aware(): void
    {
        $uploader = $this->prophesize(VideoFileUploaderInterface::class);
        $uploader->upload(Argument::cetera())->shouldNotBeCalled();
        $uploader->uploadPoster(Argument::cetera())->shouldNotBeCalled();

        (new VideoFileUploadSubscriber($uploader->reveal()))->upload(new GenericEvent(new \stdClass()));
    }
}
