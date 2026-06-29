# Setono Sylius Video Plugin

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]
[![Code Coverage][ico-code-coverage]][link-code-coverage]

Adds a **Videos** tab to the admin product create/edit page so editors can attach videos to a
product through three built-in kinds — **file upload**, **external URL** and **direct embed
code** — and renders them on the shop product page.

Videos are stored with **Single Table Inheritance** (a base `ProductVideo` plus `FileProductVideo` /
`UrlProductVideo` / `EmbedProductVideo` subtypes) and presented through **tagged composite renderers**, so the
plugin is extended exactly like [`setono/sylius-qr-code-plugin`](https://github.com/Setono/sylius-qr-code-plugin):
add a subtype + a resource entry + a renderer and the discriminator map picks it up — no edits to
the plugin's mapping required.

## Installation

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

The plugin deliberately does **not** map the inverse `videos` association for you — you own your
`Product` mapping, so you add it. The owning `ManyToOne` lives on `ProductVideo`, so you only need
the inverse `OneToMany`. The `videos` property comes from the trait, so map it with XML/YAML rather
than attributes (attributes can't target a trait's property):

```xml
<!-- config/doctrine/Product.orm.xml -->
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

Point the product resource at your class (skip if you already override it):

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
mapping is needed beyond the `videos` association you added in step 3.

### 5. Install assets

```bash
bin/console assets:install
```

This publishes the small dependency-free JavaScript controller that toggles the kind-specific
field on the adaptive entry form to match the selected type.

## Usage

- **Admin:** open any product's edit page and switch to the **Videos** tab. Add videos, pick a
  kind (file / url / embed) per row, and optionally attach a poster image. Ordering uses the
  per-row **position** field (lowest first); positions are maintained per-product by the Gedmo
  Sortable extension Sylius already enables, so a new row left blank is appended automatically.
- **Shop:** the product's videos render on the product page via the `sylius.shop.product.show.content`
  event (it always fires, unlike `before_thumbnails`, which only fires for products with more
  than one image). Disable the block with:

  ```yaml
  # config/packages/setono_sylius_video.yaml
  sylius_ui:
      events:
          sylius.shop.product.show.content:
              blocks:
                  setono_sylius_video: { enabled: false }
  ```

  Or render videos wherever you like:

  ```twig
  {% include '@SetonoSyliusVideoPlugin/shop/product/_videos.html.twig' with { product: product } %}
  ```

- **Twig functions** (output is HTML-safe):

  ```twig
  {{ setono_sylius_video_render(video) }}   {# renders a single video #}
  {{ setono_sylius_video_poster(video) }}   {# resolves a poster/thumbnail URL or null #}
  ```

## Configuration

```yaml
# config/packages/setono_sylius_video.yaml
setono_sylius_video:
    filesystem:
        # Service id of the media filesystem used to store uploaded videos and posters.
        adapter: Sylius\Component\Core\Filesystem\Adapter\FilesystemAdapterInterface
        # Public URL base that a stored media path is prefixed with.
        public_url_prefix: /media/image
```

## Overriding

- **Models:** swap any subtype via the `setono_sylius_video.resources.*.classes.model` config —
  the discriminator keys stay stable because they are derived from each model's `getType()`.
- **Renderer templates:** override at `templates/bundles/SetonoSyliusVideoPlugin/shop/renderer/<type>.html.twig`.
- **Uploads:** decorate the `setono_sylius_video.uploader` service, or point `filesystem.adapter`
  at any Flysystem/Gaufrette adapter Sylius exposes.

## Extending — adding a new video kind

Worked example: a `youtube` kind that reuses the `url` column (parse the id from it) and computes
its thumbnail from the YouTube CDN. Because it reuses an existing column **no migration is
needed**.

**1. Subtype model + interface** — name it `<Kind>ProductVideo` and the static `getType()`
derives the discriminator (`YoutubeProductVideo` → `youtube`); override it only for a
non-conventional name.

```php
namespace App\Entity\Video;

use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;

interface YoutubeProductVideoInterface extends ProductVideoInterface
{
    public function getUrl(): ?string;
    public function setUrl(?string $url): void;
    public function getVideoId(): ?string;
}

class YoutubeProductVideo extends ProductVideo implements YoutubeProductVideoInterface
{
    protected ?string $url = null;

    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $url): void { $this->url = $url; }

    public function getVideoId(): ?string
    {
        parse_str((string) parse_url((string) $this->url, \PHP_URL_QUERY), $query);

        return $query['v'] ?? null;
    }
}
```

**2. ORM mapping** — only needed if the kind adds a *new* column. Reusing the existing `url`
column needs none.

**3. Register it as a resource** — the plugin scans every Sylius resource whose model implements
`ProductVideoInterface`: the discriminator listener adds it to the STI map and the kind selector
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
tag it `setono_sylius_video.renderer`, and add a `shop/renderer/youtube.html.twig` template. Add a
poster resolver that builds the thumbnail from the parsed id and tag it
`setono_sylius_video.poster_resolver`:

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

**5. Form fields** — ship a `ProductVideoType` extension for the kind's input(s) by extending
`AbstractProductVideoTypeExtension` and tagging it `form.type_extension`. It can add as many fields
as the kind needs; the base class reveals/hides and strips them per the selected type:

```php
final class YoutubeProductVideoTypeExtension extends AbstractProductVideoTypeExtension
{
    protected function getType(): string { return 'youtube'; }
    protected function fieldNames(): array { return ['url']; }

    protected function addFields(FormInterface $form): void
    {
        if ($form->has('url')) {
            return;
        }

        $form->add('url', UrlType::class, [
            'label' => 'app.form.video.url',
            'required' => false,
            'attr' => ['data-video-fields' => 'youtube'], // groups the field under this kind for the JS toggle
        ]);
    }
}
```

**6. Validation + translations** — add a per-subtype validation file and the `setono_sylius_video.ui.types.youtube`
label key (plus labels for any fields your extension adds).

## Development & quality gates

```bash
composer analyse       # PHPStan (level max)
composer check-style   # ECS
composer fix-style     # ECS auto-fix
composer phpunit       # PHPUnit (Prophecy)
```

The test application lives in `tests/Application`. Admin credentials: `sylius` / `sylius`.

## License

This plugin is released under the MIT License. See the bundled [LICENSE](LICENSE) file.

[ico-version]: https://poser.pugx.org/setono/sylius-video-plugin/v/stable
[ico-license]: https://poser.pugx.org/setono/sylius-video-plugin/license
[ico-github-actions]: https://github.com/Setono/SyliusVideoPlugin/workflows/build/badge.svg
[ico-code-coverage]: https://codecov.io/gh/Setono/SyliusVideoPlugin/branch/1.14.x/graph/badge.svg

[link-packagist]: https://packagist.org/packages/setono/sylius-video-plugin
[link-github-actions]: https://github.com/Setono/SyliusVideoPlugin/actions
[link-code-coverage]: https://codecov.io/gh/Setono/SyliusVideoPlugin
