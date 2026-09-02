<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Boots the test application (tests/Application) in the `test` environment. None of these tests
 * open a database connection: they inspect the compiled container, Doctrine metadata, the form
 * registry and Twig, which is where the plugin's wiring can silently break.
 */
abstract class FunctionalTestCase extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    protected function service(string $id): object
    {
        $service = self::getContainer()->get($id);
        self::assertInstanceOf($id, $service);

        return $service;
    }
}
