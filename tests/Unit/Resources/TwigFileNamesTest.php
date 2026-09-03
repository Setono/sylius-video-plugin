<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Unit\Resources;

use PHPUnit\Framework\TestCase;

/**
 * Twig folders and file names are snake_case (see CLAUDE.md). This guards the rule for the
 * plugin's own templates and for the test application's, except for Sylius template overrides
 * under `templates/bundles/`, whose paths must mirror Sylius's own (PascalCase) directories.
 */
final class TwigFileNamesTest extends TestCase
{
    /**
     * @test
     */
    public function it_keeps_the_plugins_twig_folders_and_files_in_snake_case(): void
    {
        self::assertSame([], $this->offenders(\dirname(__DIR__, 3) . '/src/Resources/views'));
    }

    /**
     * @test
     */
    public function it_keeps_the_test_applications_own_templates_in_snake_case(): void
    {
        $templates = \dirname(__DIR__, 3) . '/tests/Application/templates';

        self::assertSame([], $this->offenders($templates, ['bundles']));
    }

    /**
     * @param list<string> $exemptTopLevelDirectories
     *
     * @return list<string>
     */
    private function offenders(string $root, array $exemptTopLevelDirectories = []): array
    {
        $offenders = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $file) {
            self::assertInstanceOf(\SplFileInfo::class, $file);
            $relative = substr($file->getPathname(), \strlen($root) + 1);
            $topLevel = explode(\DIRECTORY_SEPARATOR, $relative, 2)[0];

            if (\in_array($topLevel, $exemptTopLevelDirectories, true)) {
                continue;
            }

            if (1 === preg_match('/[A-Z]/', $relative)) {
                $offenders[] = $relative;
            }
        }

        sort($offenders);

        return $offenders;
    }
}
