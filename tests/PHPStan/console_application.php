<?php

declare(strict_types=1);

use Setono\SyliusVideoPlugin\Tests\Application\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;

// PHPStan asks the console application for a command's definition when it analyses the command's
// getArgument()/getOption() calls. Registering the commands instantiates them, which resolves env
// vars (APP_SECRET, DATABASE_URL, ...), so load the test application's .env files like the test
// suite does.
require __DIR__ . '/../Application/config/bootstrap.php';

$kernel = new Kernel('test', true);
$kernel->boot();

return new Application($kernel);
