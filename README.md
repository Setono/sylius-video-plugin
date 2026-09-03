# Setono Sylius Video Plugin

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]
[![Code Coverage][ico-code-coverage]][link-code-coverage]

Adds a **Videos** tab to the admin product create/edit page so editors can attach videos to a
product through three built-in types — **file upload**, **external URL** and **direct embed
code** — and renders them on the shop product page.

Videos are stored with **Single Table Inheritance** (a base `ProductVideo` plus `FileProductVideo` /
`UrlProductVideo` / `EmbedProductVideo` subtypes) and presented through **tagged composite renderers**, so the
plugin is extended exactly like [`setono/sylius-qr-code-plugin`](https://github.com/Setono/sylius-qr-code-plugin):
add a subtype + a resource entry + a renderer and the discriminator map picks it up — no edits to
the plugin's mapping required.

## Installation

### Requirements

- Sylius with the Gedmo **sortable** and **timestampable** listeners enabled (Sylius core enables
  them by default). They fill the `position`, `created_at` and `updated_at` columns of a video; an
  application that disables `stof_doctrine_extensions` cannot insert videos.
- No other runtime dependency beyond what Sylius already ships.

### 1. Require the plugin

```bash
composer require setono/sylius-video-plugin
```

### 2. Register the bundle

```php
# config/bundles.php

return [
    // ...
    Setono\SyliusVideoPlugin\SetonoSyliusVideoPlugin::class => ['all' => true],
];
```

### 3. Make your `Product` own its videos

Implement `ProductVideosAwareInterface` and use the trait on your `Product`:

```php
# src/Entity/Product/Product.php

declare(strict_types=1);

namespace App\Entity\Product;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Setono\SyliusVideoPlugin\Model\ProductVideosAwareInterface;
use Setono\SyliusVideoPlugin\Model\ProductVideosAwareTrait;
use Sylius\Component\Core\Model\Product as BaseProduct;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_product')]
class Product extends BaseProduct implements ProductVideosAwareInterface
{
    use ProductVideosAwareTrait;

    public function __construct()
    {
        parent::__construct();

        $this->videos = new ArrayCollection();
    }
}
```

That is all for an attribute-mapped `Product` (the Sylius-Standard default): the trait's `videos`
property carries the `OneToMany` attributes (mapped by `product`, cascade persist, orphan removal,
ordered by `position`), and Doctrine reads them through your class. The owning `ManyToOne` lives on
`ProductVideo`. The test application under `tests/Application` does exactly this.

If your `Product` is XML- or YAML-mapped instead, the attributes are ignored and you declare the
inverse side yourself, next to the rest of your mapping:

```xml
<!-- config/doctrine/Product.Product.orm.xml -->
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                                      https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">
    <entity name="App\Entity\Product\Product" table="sylius_product">
        <one-to-many field="videos" target-entity="Setono\SyliusVideoPlugin\Model\ProductVideo" mapped-by="product" orphan-removal="true">
            <cascade>
                <cascade-persist/>
            </cascade>
            <order-by>
                <order-by-field name="position" direction="ASC"/>
            </order-by>
        </one-to-many>
    </entity>
</doctrine-mapping>
```

Either way, point the product resource at your class (skip if you already override it):

```yaml
# config/packages/_sylius.yaml

sylius_product:
    resources:
        product:
            classes:
                model: App\Entity\Product\Product
```

### 4. Update the database

The plugin does not ship migrations — generate one against your own schema and run it:

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

This creates the single `setono_sylius_video__product_video` table. The discriminator listener
adds the Single Table Inheritance map at runtime (keyed on each subtype's `getType()`), so no extra
mapping is needed beyond the `videos` association you added in step 3. The `type` column is 64
characters wide so derived type names of custom subtypes fit; installs created with an earlier
development version (20 characters) pick the change up with another `doctrine:migrations:diff`.

### 5. Install assets

```bash
bin/console assets:install
```

This publishes the small dependency-free JavaScript controller that toggles the type-specific
field on the adaptive entry form to match the selected type, and the shop stylesheet that keeps
the video block responsive (it stretches pasted embed iframes to the 16:9 box). The stylesheet is
added to the shop layout through the `sylius.shop.layout.stylesheets` event; override or disable
that `setono_sylius_video` block if your theme ships its own styles.

### Upload limits

Video files go through the same Sylius media filesystem as product images. That adapter is
string-based, so each upload passes through PHP memory once, and the request must fit the
`upload_max_filesize`, `post_max_size` and `memory_limit` of the PHP that serves the admin. Size
those for the largest video you expect (a few hundred megabytes is a practical ceiling), and prefer
attaching very large videos as an **External URL** video hosted elsewhere.

Uploads are validated in the `sylius` group by the plugin's validation XML: a video file must
sniff as `video/mp4`, `video/webm`, `video/ogg` or `video/quicktime`, and a poster must be a JPEG,
PNG or WebP image of at most 10 MB. To tighten either (a lower video size cap, fewer types), add
your own constraints for `Setono\SyliusVideoPlugin\Model\FileProductVideo` or `ProductVideo` in
`config/validator/`; Symfony merges them with the plugin's.

## Usage

- **Admin:** open any product's edit page and switch to the **Videos** tab. Add videos, pick a
  type (file / url / embed) per row, and optionally attach a poster image. The type is fixed once
  a video is saved (to change it, remove the row and add a new one). Ordering uses the
  per-row **position** field (lowest first); positions are maintained per-product by the Gedmo
  Sortable extension Sylius already enables, so a new row left blank is appended automatically.
  Removing a video (or its product, through the orphan-removal mapping above) also deletes the
  stored video file and poster once the change is flushed.
- **Shop:** the product's videos render on the product page as a block on the
  `sylius.shop.product.show.content` event (it always fires, unlike `before_thumbnails`, which only
  fires for products with more than one image), under a "Videos" heading, after the product tabs and
  before the associations (priority 12; Sylius renders higher priorities first, tabs are 20 and
  associations 10). Move it, drop the heading or disable it by overriding the block:

  ```yaml
  # config/packages/setono_sylius_video.yaml
  sylius_ui:
      events:
          sylius.shop.product.show.content:
              blocks:
                  setono_sylius_video:
                      priority: 30                     # e.g. above the tabs
                      context: { show_heading: false }
                      # enabled: false                 # or remove it altogether
  ```

  Or render videos wherever you like:

  ```twig
  {% include '@SetonoSyliusVideoPlugin/shop/product/_videos.html.twig' with { product: product } %}
  ```

- **Twig functions** (`setono_sylius_video_render` is marked HTML-safe; `setono_sylius_video_poster`
  returns a plain URL, escaped like any other value):

  ```twig
  {{ setono_sylius_video_render(video) }}   {# renders a single video #}
  {{ setono_sylius_video_poster(video) }}   {# resolves a poster/thumbnail URL or null #}
  ```

## Configuration

```yaml
# config/packages/setono_sylius_video.yaml
setono_sylius_video:
    embed:
        # The embed type prints admin-supplied HTML unescaped (see "Security" below). Set to false
        # to remove the type from the selector, the STI map and the renderer entirely.
        enabled: true
    filesystem:
        # Service id of the media filesystem used to store uploaded videos and posters.
        adapter: Sylius\Component\Core\Filesystem\Adapter\FilesystemAdapterInterface
        # Public URL base that a stored media path is prefixed with.
        public_url_prefix: /media/image
    # Sylius resources; override a class to extend a type (a subclass keeps its parent's type name).
    resources:
        product_video:
            classes:
                model: Setono\SyliusVideoPlugin\Model\ProductVideo          # abstract base, no factory
                repository: Setono\SyliusVideoPlugin\Repository\ProductVideoRepository
        file_video:
            classes:
                model: Setono\SyliusVideoPlugin\Model\FileProductVideo
                factory: Sylius\Component\Resource\Factory\Factory
        url_video:
            classes:
                model: Setono\SyliusVideoPlugin\Model\UrlProductVideo
                factory: Sylius\Component\Resource\Factory\Factory
        embed_video:
            classes:
                model: Setono\SyliusVideoPlugin\Model\EmbedProductVideo
                factory: Sylius\Component\Resource\Factory\Factory
```

## Security

The **embed** type stores the HTML an administrator pastes and prints it on the product page with
`|raw`, on purpose: provider embed codes need their `<iframe>` and attributes intact, and a sanitizer
would break them. Treat that field as trusted input. Anyone who can edit a product can put arbitrary
markup and scripts in front of every visitor of that product page, and Sylius has no admin role finer
than "can edit products".

- If the shop does not need pasted embed codes, turn the type off with `embed.enabled: false`;
  YouTube, Vimeo and similar providers work through the **External URL** type, which only ever
  outputs an escaped `src`.
- Otherwise, consider a Content Security Policy on the shop with a `frame-src` allow-list of the
  providers you use, so a pasted `<script>` or an unexpected frame host is blocked by the browser.

## Overriding

- **Models:** swap any subtype via the `setono_sylius_video.resources.*.classes.model` config —
  the discriminator keys stay stable because they are derived from each model's `getType()`.
- **Renderer templates:** override at `templates/bundles/SetonoSyliusVideoPlugin/shop/renderer/<type>.html.twig`;
  the shop block itself is `shop/product/_videos.html.twig` and the admin tab
  `admin/product/tab/_videos.html.twig` under the same bundle directory.
- **Renderers and poster resolvers:** both are tagged composites (`setono_sylius_video.renderer`,
  `setono_sylius_video.poster_resolver`) that ask their services in descending `priority` order and
  use the first whose `supports()` returns true. The plugin's renderers run at priority 0 and its
  stored-poster resolver at 100, so tag a more specific renderer above 0 and a computed poster
  resolver below 100 if an uploaded poster should keep winning.
- **Uploads:** decorate the `setono_sylius_video.uploader` service, or point `filesystem.adapter`
  at any Flysystem/Gaufrette adapter Sylius exposes.

## Extending — adding a new video type

Worked example: a `youtube` type that extends the URL type to reuse its `url` column and accessors,
parses the video id from the link, renders YouTube's player and computes its thumbnail from the
YouTube CDN. Because it adds no column **no migration is needed**. The test application ships this
exact type under `tests/Application` (`Entity/Video`, `Form`, `Renderer`, `Poster`,
`config/validator`, `templates/video`, `translations`), so read it as a complete reference.

| Step | Artefact | Wired by |
|---|---|---|
| 1 | `<Type>ProductVideo` model (+ interface) | class name → `getType()` |
| 2 | ORM mapping (mapped superclass, fields only for new columns) | Doctrine mapping config |
| 3 | Sylius resource entry | `sylius_resource.resources.<app>.<name>` |
| 4 | Renderer (+ template) and optional poster resolver | tags `setono_sylius_video.renderer` / `.poster_resolver` |
| 5 | Form fields | a `ProductVideoType` extension, tag `form.type_extension` |
| 6 | Validation XML in group `sylius`, `setono_sylius_video.ui.types.<type>` label | Symfony validator / translations |

**1. Subtype model + interface** — name it `<Type>ProductVideo` and the static `getType()`
derives the discriminator (`YoutubeProductVideo` → `youtube`); override it only for a
non-conventional name. Extending a concrete type is fine: the derived name (`youtube`, not `url`)
keeps the two apart in the discriminator map. Two models resolving to the same type (for example
an override of `getType()` returning `url`) fail loudly at container build and metadata load, since
one would otherwise shadow the other in the map; to *replace* a built-in type's class rather than
add a type, override its `resources.*.classes.model` instead (see Configuration).

```php
namespace App\Entity\Video;

use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Setono\SyliusVideoPlugin\Model\UrlProductVideoInterface;

interface YoutubeProductVideoInterface extends UrlProductVideoInterface
{
    public function getVideoId(): ?string;
}

class YoutubeProductVideo extends UrlProductVideo implements YoutubeProductVideoInterface
{
    public function getVideoId(): ?string
    {
        $url = (string) $this->getUrl();

        if (1 === preg_match('#youtu\.be/([\w-]{11})#', $url, $matches)) {
            return $matches[1];
        }

        parse_str((string) parse_url($url, \PHP_URL_QUERY), $query);

        return is_string($query['v'] ?? null) ? $query['v'] : null;
    }
}
```

**2. ORM mapping** — every class in the discriminator map must be known to a mapping driver, even
one that adds no column, so always ship a mapping. Declare it as a mapped superclass (Sylius turns
the resource into an entity) and map fields only for new columns. With attributes that is a single
line on the class:

```php
#[ORM\MappedSuperclass]
class YoutubeProductVideo extends UrlProductVideo implements YoutubeProductVideoInterface
```

or, for an XML-mapped entity namespace, `<mapped-superclass name="App\Entity\Video\YoutubeProductVideo"/>`
in `config/doctrine/Video.YoutubeProductVideo.orm.xml`.

**3. Register it as a resource** — the plugin scans every Sylius resource whose model implements
`ProductVideoInterface`: the discriminator listener adds it to the STI map and the type selector
picks it up (label derived as `setono_sylius_video.ui.types.<type>`). No plugin config to edit, no
listener to decorate:

```yaml
sylius_resource:
    resources:
        app.youtube_video:
            classes:
                model: App\Entity\Video\YoutubeProductVideo
```

**4. Renderer + poster** — implement `VideoRendererInterface` (`instanceof YoutubeProductVideoInterface`),
tag it `setono_sylius_video.renderer` with a `priority` above 0 so it is asked before the plugin's
URL renderer (a YouTube video is a URL video too, and the composite picks the first renderer that
supports it), and add a template for it. Add a poster resolver that builds the thumbnail from the
parsed id and tag it `setono_sylius_video.poster_resolver`; the plugin's stored-poster resolver runs
at priority 100, so an uploaded poster still wins:

```yaml
# config/services.yaml
services:
    App\Renderer\YoutubeProductVideoRenderer:
        arguments: ['@twig']
        tags:
            - { name: setono_sylius_video.renderer, priority: 10 }
    App\Poster\YoutubePosterResolver:
        tags:
            - { name: setono_sylius_video.poster_resolver }
```

```php
final class YoutubePosterResolver implements VideoPosterResolverInterface
{
    public function supports(ProductVideoInterface $video): bool
    {
        return $video instanceof YoutubeProductVideoInterface && null !== $video->getVideoId();
    }

    public function resolve(ProductVideoInterface $video): ?string
    {
        \assert($video instanceof YoutubeProductVideoInterface);

        return null === $video->getVideoId()
            ? null
            : sprintf('https://img.youtube.com/vi/%s/hqdefault.jpg', $video->getVideoId());
    }
}
```

**5. Form fields** — ship a `ProductVideoType` extension for the type's input(s) by extending
`AbstractProductVideoTypeExtension` and tagging it `form.type_extension`. Declare the type and its
field(s) as a `name => [form type, options]` map; the base class adds them, reveals/hides them per
the selected type and strips them on submit when another type is chosen:

```php
final class YoutubeProductVideoTypeExtension extends AbstractProductVideoTypeExtension
{
    protected function getType(): string
    {
        return YoutubeProductVideo::getType();
    }

    protected function getFields(): array
    {
        // Field names must be unique across types (the plugin's URL type owns `url`), so name
        // yours distinctly and map it onto the inherited property with `property_path`.
        return [
            'youtube_url' => [UrlType::class, [
                'property_path' => 'url',
                'label' => 'app.form.video.youtube_url',
                'required' => false,
                'attr' => ['data-video-fields' => $this->getType()], // groups the field under this type for the JS toggle
            ]],
        ];
    }
}
```

**6. Validation + translations** — add a per-subtype validation file with its constraints in the
`sylius` group (each row is validated in `%setono_sylius_video.form.type.product_video.validation_groups%`,
`[sylius]` by default) and the `setono_sylius_video.ui.types.youtube` label key (plus labels for any
fields your extension adds).

## Development & quality gates

```bash
composer analyse       # PHPStan (level max)
composer check-style   # ECS
composer fix-style     # ECS auto-fix
composer phpunit           # PHPUnit, both suites
composer test-unit         # tests/Unit: kernel-free tests (what Infection mutates against)
composer test-functional   # tests/Functional: boots tests/Application (STI map, type registry, form, templates)
composer test-e2e          # tests/e2e: Playwright UI tests against the running test application
```

CI (`.github/workflows/build.yaml`) enforces more than the composer scripts: `vendor/bin/rector process --dry-run`,
`vendor/bin/infection` (`minCoveredMsi` 100, `minMsi` ratcheted in `infection.json.dist`),
`vendor/bin/composer-dependency-analyser`, `composer validate --strict`, `composer normalize --dry-run`,
and `lint:yaml`, `lint:twig` and `lint:container` in the test application. Run them before pushing.

The test application lives in `tests/Application`. Admin credentials: `sylius` / `sylius`.

## License

This plugin is released under the MIT License. See the bundled [LICENSE](LICENSE) file.

[ico-version]: https://poser.pugx.org/setono/sylius-video-plugin/v/stable
[ico-license]: https://poser.pugx.org/setono/sylius-video-plugin/license
[ico-github-actions]: https://github.com/Setono/sylius-video-plugin/workflows/build/badge.svg
[ico-code-coverage]: https://codecov.io/gh/Setono/sylius-video-plugin/branch/master/graph/badge.svg

[link-packagist]: https://packagist.org/packages/setono/sylius-video-plugin
[link-github-actions]: https://github.com/Setono/sylius-video-plugin/actions
[link-code-coverage]: https://codecov.io/gh/Setono/sylius-video-plugin
