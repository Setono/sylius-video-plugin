<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToExclude(__DIR__ . '/tests')
    // Wired through Doctrine ORM XML mapping (`<gedmo:timestampable>` on createdAt /
    // updatedAt fields), not via a PHP `use`. The analyser only inspects PHP sources so
    // it cannot see the dependency.
    ->ignoreErrorsOnPackage('gedmo/doctrine-extensions', [ErrorType::UNUSED_DEPENDENCY])
    // Wired through the `Expression` constraint in Resources/config/validation/FileVideo.xml
    // (the validator evaluates it via ExpressionLanguage), not via a PHP `use`.
    ->ignoreErrorsOnPackage('symfony/expression-language', [ErrorType::UNUSED_DEPENDENCY])
    // The Sylius split bundles/components we require are provided at install time by the
    // sylius/sylius monorepo (it `replace`s them), so the analyser attributes their classes to
    // sylius/sylius and cannot see the split packages being used directly.
    ->ignoreErrorsOnPackage('sylius/admin-bundle', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('sylius/core', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('sylius/core-bundle', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('sylius/product-bundle', [ErrorType::UNUSED_DEPENDENCY])
    // SyliusUiBundle is a runtime dependency (sylius_ui events + the admin form theme); no PHP
    // class from it is referenced directly.
    ->ignoreErrorsOnPackage('sylius/ui-bundle', [ErrorType::UNUSED_DEPENDENCY])
    // sylius/sylius is the monorepo that provides the split packages above; it is present
    // transitively, never declared directly.
    ->ignoreErrorsOnPackage('sylius/sylius', [ErrorType::SHADOW_DEPENDENCY])
;
