<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Validation;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;
use Setono\SyliusVideoPlugin\Model\FileProductVideo;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates real models against the plugin's validation XML, so the upload rules are tested
 * exactly as Symfony reads them.
 */
final class UploadConstraintsTest extends TestCase
{
    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * @test
     */
    public function it_accepts_a_video_file_of_an_allowed_type(): void
    {
        $video = new FileProductVideo();
        $video->setFile($this->upload("\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom" . str_repeat("\0", 64), 'clip.mp4'));

        self::assertCount(0, $this->validator()->validate($video, null, ['sylius']));
    }

    /**
     * @test
     */
    public function it_rejects_a_video_file_whose_content_is_not_a_video(): void
    {
        $video = new FileProductVideo();
        $video->setFile($this->upload('<?php echo 1;', 'clip.mp4'));

        $violations = $this->validator()->validate($video, null, ['sylius']);

        self::assertCount(1, $violations);
        self::assertSame('file', $violations->get(0)->getPropertyPath());
        self::assertSame(File::INVALID_MIME_TYPE_ERROR, $violations->get(0)->getCode());
    }

    /**
     * @test
     */
    public function it_accepts_an_image_poster_on_any_video_type(): void
    {
        $video = new EmbedProductVideo();
        $video->setHtml('<iframe src="https://example.com/embed"></iframe>');
        $video->setPosterFile($this->upload((string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==', true), 'poster.png'));

        self::assertCount(0, $this->validator()->validate($video, null, ['sylius']));
    }

    /**
     * @test
     */
    public function it_rejects_a_poster_that_is_not_an_image(): void
    {
        $video = new EmbedProductVideo();
        $video->setHtml('<iframe src="https://example.com/embed"></iframe>');
        $video->setPosterFile($this->upload('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'poster.png'));

        $violations = $this->validator()->validate($video, null, ['sylius']);

        self::assertCount(1, $violations);
        self::assertSame('posterFile', $violations->get(0)->getPropertyPath());
        self::assertSame(Image::INVALID_MIME_TYPE_ERROR, $violations->get(0)->getCode());
    }

    /**
     * @test
     */
    public function it_only_applies_the_upload_rules_in_the_sylius_group(): void
    {
        $video = new FileProductVideo();
        $video->setFile($this->upload('plain text', 'clip.mp4'));

        self::assertCount(0, $this->validator()->validate($video, null, ['Default']));
    }

    private function validator(): ValidatorInterface
    {
        $validation = \dirname(__DIR__, 2) . '/src/Resources/config/validation';

        return Validation::createValidatorBuilder()
            ->addXmlMapping($validation . '/ProductVideo.xml')
            ->addXmlMapping($validation . '/FileProductVideo.xml')
            ->getValidator();
    }

    private function upload(string $content, string $name): UploadedFile
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'setono_video_upload');
        file_put_contents($path, $content);
        $this->files[] = $path;

        return new UploadedFile($path, $name, null, null, true);
    }
}
