<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Functional;

use Twig\Environment;

final class TemplatesTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_compiles_every_template_the_plugin_ships(): void
    {
        $twig = $this->service(Environment::class);
        $views = \dirname(__DIR__, 2) . '/src/Resources/views';

        $templates = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($views, \FilesystemIterator::SKIP_DOTS)) as $file) {
            self::assertInstanceOf(\SplFileInfo::class, $file);
            $templates[] = '@SetonoSyliusVideoPlugin/' . substr($file->getPathname(), \strlen($views) + 1);
        }

        self::assertNotEmpty($templates);

        foreach ($templates as $template) {
            $twig->load($template);
        }

        self::assertContains('@SetonoSyliusVideoPlugin/shop/product/_videos.html.twig', $templates);
    }
}
