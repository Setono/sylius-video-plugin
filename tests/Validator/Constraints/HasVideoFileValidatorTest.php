<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Validator\Constraints;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\Model\FileProductVideo;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Setono\SyliusVideoPlugin\Validator\Constraints\HasVideoFile;
use Setono\SyliusVideoPlugin\Validator\Constraints\HasVideoFileValidator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Validation;

final class HasVideoFileValidatorTest extends TestCase
{
    /**
     * @test
     */
    public function it_reports_a_missing_file_on_the_file_field(): void
    {
        $violations = Validation::createValidator()->validate(new FileProductVideo(), new HasVideoFile());

        self::assertCount(1, $violations);
        self::assertSame('file', $violations->get(0)->getPropertyPath());
        self::assertSame('setono_sylius_video.file_video.file.not_blank', $violations->get(0)->getMessage());
    }

    /**
     * @test
     */
    public function it_accepts_a_stored_path(): void
    {
        $video = new FileProductVideo();
        $video->setPath('video/ab/stored.mp4');

        self::assertCount(0, Validation::createValidator()->validate($video, new HasVideoFile()));
    }

    /**
     * @test
     */
    public function it_accepts_a_pending_upload(): void
    {
        $video = new FileProductVideo();
        $video->setFile(new \SplFileInfo(__FILE__));

        self::assertCount(0, Validation::createValidator()->validate($video, new HasVideoFile()));
    }

    /**
     * @test
     */
    public function it_only_validates_file_videos(): void
    {
        $this->expectException(UnexpectedValueException::class);

        (new HasVideoFileValidator())->validate(new UrlProductVideo(), new HasVideoFile());
    }

    /**
     * @test
     */
    public function it_only_backs_its_own_constraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        (new HasVideoFileValidator())->validate(new FileProductVideo(), new NotBlank());
    }
}
