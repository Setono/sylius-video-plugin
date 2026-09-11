<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Form\Extension;

use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\Form\Extension\CloudflareStreamProductVideoTypeExtension;
use Setono\SyliusVideoPlugin\Form\Extension\UrlProductVideoTypeExtension;
use Setono\SyliusVideoPlugin\Form\Type\ProductVideoType;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Setono\SyliusVideoPlugin\Type\VideoTypeRegistry;
use Sylius\Component\Resource\Factory\Factory;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CloudflareStreamProductVideoTypeExtensionTest extends TypeTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_adds_an_unmapped_file_picker_wired_to_the_direct_upload_endpoint_and_a_hidden_uid(): void
    {
        $form = $this->factory->create(ProductVideoType::class);

        self::assertTrue($form->has('cloudflare_stream_file'));
        self::assertTrue($form->has('cloudflare_stream_uid'));

        $file = $form->get('cloudflare_stream_file')->getConfig();
        self::assertSame(FileType::class, $file->getType()->getInnerType()::class);
        self::assertFalse($file->getMapped());
        self::assertFalse($file->getRequired());
        self::assertSame('setono_sylius_video.form.video.cloudflare_stream_file', $file->getOption('label'));
        self::assertSame('setono_sylius_video.form.video.help.cloudflare_stream_file', $file->getOption('help'));
        self::assertSame([
            'data-video-fields' => 'cloudflare_stream',
            'accept' => 'video/*',
            'data-cloudflare-stream-upload' => '/admin/videos/cloudflare-stream/direct-upload',
            'data-cloudflare-stream-chunk-size' => 52_428_800,
            'data-cloudflare-stream-messages' => json_encode([
                'preparing' => 'setono_sylius_video.ui.cloudflare_stream.preparing:t',
                'uploading' => 'setono_sylius_video.ui.cloudflare_stream.uploading:t',
                'uploaded' => 'setono_sylius_video.ui.cloudflare_stream.uploaded:t',
                'failed' => 'setono_sylius_video.ui.cloudflare_stream.failed:t',
            ]),
        ], $file->getOption('attr'));

        $uid = $form->get('cloudflare_stream_uid')->getConfig();
        self::assertSame(HiddenType::class, $uid->getType()->getInnerType()::class);
        self::assertSame('uid', (string) $uid->getPropertyPath());
        self::assertSame(['data-video-fields' => 'cloudflare_stream', 'data-cloudflare-stream-uid' => true], $uid->getOption('attr'));
    }

    /**
     * @test
     */
    public function it_keeps_the_chunk_size_within_cloudflares_limits(): void
    {
        // Read through constant() so the assertions are not folded away by static analysis.
        $chunkSize = constant(CloudflareStreamProductVideoTypeExtension::class . '::CHUNK_SIZE');
        self::assertIsInt($chunkSize);
        self::assertGreaterThanOrEqual(5_242_880, $chunkSize);
        self::assertLessThanOrEqual(209_715_200, $chunkSize);
        self::assertSame(0, $chunkSize % 262_144);
    }

    /**
     * @test
     */
    public function it_submits_only_the_uid_for_a_new_cloudflare_stream_video(): void
    {
        $form = $this->factory->create(ProductVideoType::class);

        $form->submit(['type' => 'cloudflare_stream', 'cloudflare_stream_uid' => 'video123', 'position' => '1']);

        self::assertTrue($form->isSynchronized());

        $video = $form->getData();
        self::assertInstanceOf(CloudflareStreamProductVideo::class, $video);
        self::assertSame('video123', $video->getUid());
        self::assertFalse($video->isReady());
        self::assertSame(1, $video->getPosition());
    }

    /**
     * @test
     */
    public function it_shows_the_uid_of_a_saved_video_and_none_of_the_other_types_fields(): void
    {
        $video = new CloudflareStreamProductVideo();
        $video->setUid('video123');

        $form = $this->factory->create(ProductVideoType::class, $video);

        self::assertSame('video123', $form->get('cloudflare_stream_uid')->getData());
        self::assertTrue($form->has('cloudflare_stream_file'));
        self::assertFalse($form->has('url'));
    }

    /**
     * @test
     */
    public function it_reports_a_missing_upload_on_the_file_picker_rather_than_the_hidden_uid(): void
    {
        $form = $this->factory->create(ProductVideoType::class);

        $form->submit(['type' => 'cloudflare_stream', 'cloudflare_stream_uid' => '']);

        self::assertFalse($form->isValid());
        self::assertCount(1, $form->get('cloudflare_stream_file')->getErrors());
        self::assertSame('setono_sylius_video.cloudflare_stream_video.uid.not_blank', $form->get('cloudflare_stream_file')->getErrors()[0]->getMessageTemplate());
        self::assertCount(0, $form->get('cloudflare_stream_uid')->getErrors());
        self::assertCount(0, $form->getErrors());
    }

    /**
     * @test
     */
    public function it_strips_its_fields_when_another_type_is_submitted(): void
    {
        $form = $this->factory->create(ProductVideoType::class);

        $form->submit(['type' => 'url', 'url' => 'https://example.com/video', 'cloudflare_stream_uid' => 'video123']);

        self::assertTrue($form->isSynchronized());
        self::assertInstanceOf(UrlProductVideo::class, $form->getData());
        self::assertFalse($form->has('cloudflare_stream_uid'));
        self::assertFalse($form->has('cloudflare_stream_file'));
    }

    /**
     * @return list<\Symfony\Component\Form\FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        $registry = new VideoTypeRegistry([
            ['type' => 'url', 'label' => 'setono_sylius_video.ui.types.url', 'factory' => new Factory(UrlProductVideo::class)],
            ['type' => 'cloudflare_stream', 'label' => 'setono_sylius_video.ui.types.cloudflare_stream', 'factory' => new Factory(CloudflareStreamProductVideo::class)],
        ]);

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate('setono_sylius_video_admin_cloudflare_stream_direct_upload')->willReturn('/admin/videos/cloudflare-stream/direct-upload');

        $translator = $this->prophesize(TranslatorInterface::class);
        $translator->trans(\Prophecy\Argument::type('string'))->will(static function (array $arguments): string {
            \assert(is_string($arguments[0]));

            return $arguments[0] . ':t';
        });

        return [
            new PreloadedExtension(
                [new ProductVideoType(ProductVideo::class, ['sylius'], $registry)],
                [ProductVideoType::class => [
                    new UrlProductVideoTypeExtension(),
                    new CloudflareStreamProductVideoTypeExtension($urlGenerator->reveal(), $translator->reveal()),
                ]],
            ),
            new ValidatorExtension(
                Validation::createValidatorBuilder()
                    ->addXmlMapping(__DIR__ . '/../../../../src/Resources/config/validation/CloudflareStreamProductVideo.xml')
                    ->getValidator(),
            ),
        ];
    }
}
