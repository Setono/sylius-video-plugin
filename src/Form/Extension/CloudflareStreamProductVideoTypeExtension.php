<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Form\Extension;

use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideo;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The Cloudflare Stream type's fields: a file picker the admin JavaScript uploads from straight to
 * Cloudflare (direct creator upload over tus, chunked) and a hidden uid it fills in when the upload
 * has finished. Only the uid reaches the application; the file input itself is not mapped.
 */
final class CloudflareStreamProductVideoTypeExtension extends AbstractProductVideoTypeExtension
{
    /**
     * Cloudflare accepts chunks of 5 MiB to 200 MiB that are multiples of 256 KiB and recommends
     * 50 MiB for reliable connections.
     */
    public const CHUNK_SIZE = 52_428_800;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // The uid is a hidden field, whose errors would bubble up and out of sight; show "upload a
        // video first" on the file picker the admin actually interacts with.
        $resolver->addNormalizer('error_mapping', static fn (Options $options, mixed $errorMapping): array => (is_array($errorMapping) ? $errorMapping : []) + ['uid' => 'cloudflare_stream_file']);
    }

    protected function getType(): string
    {
        return CloudflareStreamProductVideo::getType();
    }

    protected function getFields(): array
    {
        $type = $this->getType();

        return [
            'cloudflare_stream_file' => [FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'setono_sylius_video.form.video.cloudflare_stream_file',
                'help' => 'setono_sylius_video.form.video.help.cloudflare_stream_file',
                'attr' => [
                    'data-video-fields' => $type,
                    'accept' => 'video/*',
                    // The admin endpoint that creates the one-time upload URL; its presence is what
                    // activates the direct-upload controller in setono-sylius-video-plugin.js.
                    'data-cloudflare-stream-upload' => $this->urlGenerator->generate('setono_sylius_video_admin_cloudflare_stream_direct_upload'),
                    'data-cloudflare-stream-chunk-size' => self::CHUNK_SIZE,
                    'data-cloudflare-stream-messages' => json_encode($this->messages(), \JSON_THROW_ON_ERROR),
                ],
            ]],
            'cloudflare_stream_uid' => [HiddenType::class, [
                'property_path' => 'uid',
                'required' => false,
                'attr' => ['data-video-fields' => $type, 'data-cloudflare-stream-uid' => true],
            ]],
        ];
    }

    /**
     * Status texts for the upload controller, translated here since the script is a static asset.
     *
     * @return array<string, string>
     */
    private function messages(): array
    {
        $messages = [];

        foreach (['preparing', 'uploading', 'uploaded', 'failed'] as $key) {
            $messages[$key] = $this->translator->trans('setono_sylius_video.ui.cloudflare_stream.' . $key);
        }

        return $messages;
    }
}
