<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Sanitizer;

use PHPUnit\Framework\TestCase;
use Setono\SyliusVideoPlugin\Sanitizer\PassthroughEmbedSanitizer;

final class PassthroughEmbedSanitizerTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_embed_code_unchanged(): void
    {
        $code = '<iframe src="https://www.youtube.com/embed/abc"></iframe>';

        self::assertSame($code, (new PassthroughEmbedSanitizer())->sanitize($code));
    }
}
