<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Validation;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideo;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates the Cloudflare Stream model against the plugin's validation XML, exactly as Symfony reads it.
 */
final class CloudflareStreamConstraintsTest extends TestCase
{
    /**
     * @test
     */
    public function it_requires_a_uid_so_a_row_cannot_be_saved_before_the_upload_finished(): void
    {
        $violations = $this->validator()->validate(new CloudflareStreamProductVideo(), null, ['sylius']);

        self::assertCount(1, $violations);
        self::assertSame('uid', $violations->get(0)->getPropertyPath());
        self::assertSame('setono_sylius_video.cloudflare_stream_video.uid.not_blank', $violations->get(0)->getMessageTemplate());
    }

    /**
     * @test
     */
    public function it_accepts_a_video_with_a_uid(): void
    {
        $video = new CloudflareStreamProductVideo();
        $video->setUid('video123');

        self::assertCount(0, $this->validator()->validate($video, null, ['sylius']));
    }

    /**
     * @test
     */
    public function it_only_applies_the_rule_in_the_sylius_group(): void
    {
        self::assertCount(0, $this->validator()->validate(new CloudflareStreamProductVideo(), null, ['Default']));
    }

    private function validator(): ValidatorInterface
    {
        $validation = \dirname(__DIR__, 3) . '/src/Resources/config/validation';

        return Validation::createValidatorBuilder()
            ->addXmlMapping($validation . '/ProductVideo.xml')
            ->addXmlMapping($validation . '/CloudflareStreamProductVideo.xml')
            ->getValidator();
    }
}
