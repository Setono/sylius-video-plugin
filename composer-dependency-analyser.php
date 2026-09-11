<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToExclude(__DIR__ . '/tests')
    // Only referenced as the `http_client` service id (services/cloudflare_stream.xml); requiring
    // the package is what guarantees FrameworkBundle registers that service.
    ->ignoreErrorsOnPackage('symfony/http-client', [ErrorType::UNUSED_DEPENDENCY])
;
