<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\Twig\VideoTwigExtension;
use Twig\TwigFunction;

final class VideoTwigExtensionTest extends TestCase
{
    /**
     * @test
     */
    public function it_registers_the_render_poster_and_media_url_functions(): void
    {
        $functions = (new VideoTwigExtension())->getFunctions();

        $names = array_map(static fn (TwigFunction $function): string => $function->getName(), $functions);

        self::assertSame(['setono_sylius_video_render', 'setono_sylius_video_poster', 'setono_sylius_video_media_url'], $names);
    }

    /**
     * @test
     */
    public function it_marks_the_render_function_as_html_safe(): void
    {
        $render = (new VideoTwigExtension())->getFunctions()[0];
        $safe = $render->getSafe(new \Twig\Node\Node());

        self::assertSame('setono_sylius_video_render', $render->getName());
        self::assertIsArray($safe);
        self::assertContains('html', $safe);
    }
}
