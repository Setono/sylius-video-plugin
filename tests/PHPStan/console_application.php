<?php

declare(strict_types=1);

use Setono\SyliusVideoPlugin\Tests\Application\Kernel;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;

// PHPStan asks this application for a command's definition when it analyses the command's
// getArgument()/getOption() calls. Only the plugin's own commands are registered: registering every
// command of the test application instantiates them all, and some (Symfony's translation extractor
// on the lowest supported versions) then trip over the php-parser version bundled with PHPStan.
// Instantiating a command resolves env vars, so the test application's .env files are loaded first.
require __DIR__ . '/../Application/config/bootstrap.php';

$kernel = new Kernel('test', true);
$kernel->boot();

$commandLoader = $kernel->getContainer()->get('console.command_loader');

if (!$commandLoader instanceof CommandLoaderInterface) {
    throw new LogicException('The test application has no console command loader.');
}

$application = new Application();

foreach ($commandLoader->getNames() as $name) {
    if (str_starts_with($name, 'setono:sylius-video:')) {
        $application->add($commandLoader->get($name));
    }
}

return $application;
