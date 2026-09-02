# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

`setono/sylius-video-plugin` lets merchants attach videos to Sylius products. Three built-in types
share one Single Table Inheritance entity: the abstract `ProductVideo` base and the
`FileProductVideo` (uploaded file), `UrlProductVideo` (external link; a provider page renders in an
`<iframe>`, a direct file in a native `<video>`) and `EmbedProductVideo` (pasted embed HTML, printed
raw) subtypes under `src/Model/`. Videos hang off `Product` through a `videos` collection that the
application maps itself (`ProductVideosAwareInterface` + `ProductVideosAwareTrait`); the plugin
ships neither migrations nor a Product mapping.

How the pieces fit together:

- **Types are discovered, not configured.** `Type\ProductVideoTypes::fromResources()` scans
  `%sylius.resources%` for models implementing `ProductVideoInterface`. The Doctrine listener
  `ProductVideoDiscriminatorMapListener` builds the STI map from it and the compiler pass
  `RegisterVideoTypesPass` builds the `VideoTypeRegistry` (type choices + factories) for the form.
  A type's discriminator value is derived from its class name by `ProductVideo::getType()`
  (`UrlProductVideo` → `url`); duplicate values throw.
- **Admin form.** `Form\Extension\ProductTypeExtension` adds a `videos` collection to the Sylius
  product form. `Form\Type\ProductVideoType` carries the shared fields (position, poster, the `type`
  select, locked once a row is saved) and each type contributes its own inputs through an
  `AbstractProductVideoTypeExtension` subclass (`getType()` + `getFields()`). The Videos tab is added
  by `EventSubscriber\ProductFormMenuSubscriber`; `Resources/public/setono-sylius-video-plugin.js`
  shows only the selected type's fields.
- **Shop rendering.** `Twig\VideoTwigExtension` / `Twig\VideoRuntime` expose
  `setono_sylius_video_render()` and `setono_sylius_video_poster()`. `Renderer\CompositeVideoRenderer`
  dispatches to services tagged `setono_sylius_video.renderer`, `Poster\CompositeVideoPosterResolver`
  to `setono_sylius_video.poster_resolver` (both via `setono/composite-compiler-pass`, wired in
  `SetonoSyliusVideoPlugin::build()`). Templates live in `Resources/views/shop/renderer/<type>.html.twig`;
  the product-page block and the stylesheet are prepended as `sylius_ui` blocks in
  `DependencyInjection\SetonoSyliusVideoExtension::prepend()`.
- **Media.** `Uploader\VideoMediaUploader` stores files and posters on Sylius's media filesystem
  (`setono_sylius_video.filesystem`), `Filesystem\MediaUrlGenerator` builds public URLs,
  `EventSubscriber\VideoFileUploadSubscriber` uploads pending files on product create/update and
  `EventListener\Doctrine\ProductVideoFilesRemovalListener` deletes files after a video is removed.
  Uploads are validated by the `Validator\Constraints\VideoUpload` / `PosterUpload` constraints
  against the limits under `setono_sylius_video.upload`.
- **Service wiring** is explicit: one XML file per `src/` folder under `Resources/config/services/`,
  service ids are the FQCN, interfaces are aliases to the implementation, no autowiring or
  autoconfiguration. Configuration lives in `DependencyInjection\Configuration`.

## Code Standards

Follow clean code principles and SOLID design patterns when working with this codebase:
- Write clean, readable, and maintainable code
- Apply SOLID principles (Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion)
- Use meaningful variable and method names
- Keep methods and classes focused on a single responsibility
- Favor composition over inheritance
- Write code that is easy to test and extend

House rules that reviews enforce:
- No migrations in the plugin; no automatic mapping onto the application's `Product`.
- Symfony listeners go in `src/EventSubscriber/` as `EventSubscriberInterface`; Doctrine listeners in `src/EventListener/Doctrine/`.
- Validation rules belong in `src/Resources/config/validation/*.xml` (group `sylius`), not in form types.
- Prefer discovery through Sylius resources over hand-maintained config; derive names from `getType()`.
- Keep the terminology "type" (never "kind") for the video subtypes.
- Twig folders and file names are snake_case (`admin/form/_videos_theme.html.twig`, `shop/renderer/url.html.twig`); never camelCase.

### Testing Requirements
- Write unit tests for all new functionality (if it makes sense)
- Follow the BDD-style naming convention for test methods (e.g., `it_should_do_something_when_condition_is_met`)
- **MUST use Prophecy for mocking** - Use the `ProphecyTrait` and `$this->prophesize()` for all mocks, NOT PHPUnit's `$this->createMock()`
- **Form testing** - Use Symfony's best practices for form testing as documented at https://symfony.com/doc/current/form/unit_testing.html
  - Extend `Symfony\Component\Form\Test\TypeTestCase` for form type tests
  - Use `$this->factory->create()` to create form instances
  - Test form submission, validation, and data transformation
- Ensure tests are isolated and don't depend on external state
- Test both happy path and edge cases
- Infection runs in CI with `minCoveredMsi: 100` and `minMsi` ratcheted to the current score in
  `infection.json.dist`; new production code needs tests that kill its mutants.

## Development Commands

Based on the `composer.json` scripts section:

### Code Quality & Testing
- `composer analyse` - Run PHPStan static analysis (level max)
- `composer check-style` - Check code style with ECS (Easy Coding Standard)
- `composer fix-style` - Fix code style issues automatically with ECS
- `composer phpunit` - Run PHPUnit tests (both suites); `composer test-unit` (`tests/Unit`, kernel-free, what Infection mutates against) and `composer test-functional` (`tests/Functional`, boots the test application)

CI (`.github/workflows/build.yaml`) additionally runs `vendor/bin/rector process --dry-run`,
`vendor/bin/infection`, `vendor/bin/composer-dependency-analyser`, `composer validate --strict`,
`composer normalize --dry-run`, and `lint:yaml` / `lint:twig` / `lint:container` in the test app;
run them locally before pushing.

### Static Analysis

#### PHPStan Configuration
PHPStan is configured in `phpstan.neon` with:
- **Analysis Level**: max (strictest)
- **Extensions**: Auto-loaded via `phpstan/extension-installer`
  - `phpstan/phpstan-symfony` - Symfony framework integration
  - `phpstan/phpstan-doctrine` - Doctrine ORM integration
  - `phpstan/phpstan-phpunit` - PHPUnit test integration
  - `jangregor/phpstan-prophecy` - Prophecy mocking integration
- **Symfony Integration**: Uses console application loader (`tests/PHPStan/console_application.php`)
- **Doctrine Integration**: Uses object manager loader (`tests/PHPStan/object_manager.php`)
- **Exclusions**: Test application directory and Configuration.php
- **Baseline**: Generate with `composer analyse -- --generate-baseline` to track improvements

### Test Application
The plugin includes a test Symfony application in `tests/Application/` for development and testing:
- Navigate to `tests/Application/` directory
- It uses its own database, `setono_sylius_video_%kernel.environment%` (see `.env`); create it with
  `doctrine:database:create`, `doctrine:schema:update --force` and `sylius:fixtures:load`
- Run `yarn install && yarn build` to build assets, and `assets:install` to publish the plugin's JS/CSS
- Serve it with `symfony serve` and use standard Symfony commands
- **Sylius Backend Credentials**: Username: `sylius`, Password: `sylius`

## Bash Tools Recommendations

Use the right tool for the right job when executing bash commands:

- **Finding FILES?** → Use `fd` (fast file finder)
- **Finding TEXT/strings?** → Use `rg` (ripgrep for text search)
- **Finding CODE STRUCTURE?** → Use `ast-grep` (syntax-aware code search)
- **SELECTING from multiple results?** → Pipe to `fzf` (interactive fuzzy finder)
- **Interacting with JSON?** → Use `jq` (JSON processor)
- **Interacting with YAML or XML?** → Use `yq` (YAML/XML processor)

Examples:
- `fd "*.php" | fzf` - Find PHP files and interactively select one
- `rg "function.*validate" | fzf` - Search for validation functions and select
- `ast-grep --lang php -p 'class $name extends $parent'` - Find class inheritance patterns

## Architecture Overview

### Translations
The plugin provides multilingual support through translation files in `src/Resources/translations/`:

- **Translation Files**: 16 locales (cs, da, de, en, es, fi, fr, hu, it, nl, no, pl, pt, ro, sv, uk); every key must exist in all of them
- **Translation Domain**: `messages.*` only (no flash messages)

Translation keys:
- `setono_sylius_video.ui.videos` - The Videos tab and shop heading
- `setono_sylius_video.ui.types.<type>` - Type labels, derived from `getType()` (a new type needs one)
- `setono_sylius_video.ui.video_of` - Accessible name of a rendered video (`%product%` placeholder)
- `setono_sylius_video.form.product.videos` and `setono_sylius_video.form.video.*` - Form labels
- `setono_sylius_video.form.video.help.*` - Field help texts (`type_locked` for a saved row)
- `setono_sylius_video.file_video.file.not_blank` - Validation message of `HasVideoFile`
