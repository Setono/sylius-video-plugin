<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusVideoPlugin\Model\CloudflareStreamProductVideo;
use Setono\SyliusVideoPlugin\Model\FileProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Sylius\Component\Core\Model\ProductInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Renders the real shop renderer templates (with strict variables) so a broken variable or a
 * missing accessible name is caught here rather than on a product page.
 */
final class ShopRendererTemplatesTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_titles_the_url_iframe_after_the_product(): void
    {
        $html = $this->render('url', $this->video(new UrlProductVideo(), 'Everyday white basic T-Shirt'), [
            'url' => 'https://www.youtube.com/embed/abc',
            'is_direct_file' => false,
        ]);

        self::assertStringContainsString('<iframe src="https://www.youtube.com/embed/abc" title="setono_sylius_video.ui.video_of:Everyday white basic T-Shirt"', $html);
    }

    /**
     * @test
     */
    public function it_labels_the_native_players_after_the_product(): void
    {
        $url = $this->render('url', $this->video(new UrlProductVideo(), 'Shirt'), [
            'url' => 'https://cdn.example.com/clip.mp4',
            'is_direct_file' => true,
        ]);
        $file = $this->render('file', $this->video(new FileProductVideo(), 'Shirt'), [
            'url' => '/media/image/video/ab/cd.mp4',
        ]);

        self::assertStringContainsString('<video class="setono-sylius-video__player" controls preload="metadata" aria-label="setono_sylius_video.ui.video_of:Shirt"', $url);
        self::assertStringContainsString('<video class="setono-sylius-video__player" controls preload="metadata" aria-label="setono_sylius_video.ui.video_of:Shirt"', $file);
    }

    /**
     * @test
     */
    public function it_renders_a_cloudflare_stream_video_as_a_video_js_player_with_both_manifests(): void
    {
        $video = new CloudflareStreamProductVideo();
        $video->setPosterPath('video/poster/ab/cd.jpg');

        $html = $this->render('cloudflare_stream', $this->video($video, 'Shirt'), [
            'hls_url' => 'https://customer-abc.cloudflarestream.com/video123/manifest/video.m3u8',
            'dash_url' => 'https://customer-abc.cloudflarestream.com/video123/manifest/video.mpd',
        ]);

        self::assertStringContainsString('<video class="video-js setono-sylius-video__player" data-setono-sylius-video-player controls playsinline preload="metadata" aria-label="setono_sylius_video.ui.video_of:Shirt" poster="video/poster/ab/cd.jpg">', $html);
        self::assertStringContainsString('<source src="https://customer-abc.cloudflarestream.com/video123/manifest/video.m3u8" type="application/x-mpegURL">', $html);
        self::assertStringContainsString('<source src="https://customer-abc.cloudflarestream.com/video123/manifest/video.mpd" type="application/dash+xml">', $html);
    }

    /**
     * @test
     */
    public function it_renders_without_a_product(): void
    {
        $html = $this->render('file', new FileProductVideo(), ['url' => '/media/image/video/ab/cd.mp4']);

        self::assertStringContainsString('aria-label="setono_sylius_video.ui.video_of:"', $html);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function render(string $template, ProductVideoInterface $video, array $context): string
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__ . '/../../../src/Resources/views', 'SetonoSyliusVideoPlugin');

        $twig = new Environment($loader, ['strict_variables' => true]);
        $twig->addFunction(new TwigFunction('setono_sylius_video_poster', static fn (ProductVideoInterface $video): ?string => $video->getPosterPath()));
        // A stand-in for Symfony's trans filter: "<key>:<parameter values>", enough to assert on.
        $trans = static function (string $id, array $parameters = []): string {
            $values = [];

            foreach ($parameters as $value) {
                $values[] = is_scalar($value) ? (string) $value : '';
            }

            return $id . ':' . implode(',', $values);
        };
        $twig->addFilter(new TwigFilter('trans', $trans));

        return $twig->render(sprintf('@SetonoSyliusVideoPlugin/shop/renderer/%s.html.twig', $template), ['video' => $video] + $context);
    }

    private function video(ProductVideoInterface $video, string $productName): ProductVideoInterface
    {
        $product = $this->prophesize(ProductInterface::class);
        $product->getName()->willReturn($productName);
        $video->setProduct($product->reveal());

        return $video;
    }
}
