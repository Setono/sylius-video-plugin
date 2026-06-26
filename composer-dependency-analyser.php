<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToExclude(__DIR__ . '/tests')
    // The plugin correctly declares the split Sylius bundles/components it integrates with, but
    // the dev toolchain (setono/sylius-plugin-pack) installs the `sylius/sylius` monorepo, which
    // `replace`s those split packages. The analyser therefore attributes their classes to
    // sylius/sylius and cannot see the split packages being used. These ignores cover only that
    // monorepo/replace artefact — remove them once the toolchain installs the split packages.
    ->ignoreErrorsOnPackage('sylius/sylius', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackage('sylius/admin-bundle', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('sylius/core', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('sylius/core-bundle', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('sylius/product-bundle', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('sylius/ui-bundle', [ErrorType::UNUSED_DEPENDENCY])
;
