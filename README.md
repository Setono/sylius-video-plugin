# Setono Sylius Video Plugin

[![Latest Version][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE)
[![Build Status][ico-github-actions]][link-github-actions]
[![Code Coverage][ico-code-coverage]][link-code-coverage]

Adds a **Videos** tab to the admin product create/edit page so editors can attach videos to a
product through three built-in kinds — **file upload**, **external URL** and **direct embed
code** — and renders them on the shop product page.

Videos are stored with **Single Table Inheritance** (a base `ProductVideo` plus `FileVideo` /
`UrlVideo` / `EmbedVideo` subtypes) and presented through **tagged composite renderers**, so the
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

The inverse `videos` association is mapped automatically onto any `Product` implementing
`ProductVideosAwareInterface` — you only need to implement the interface and use the trait:

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

```bash
bin/console doctrine:migrations:migrate
```

The plugin ships a migration creating the single `setono_sylius_video__product_video` table. The
discriminator listener and the association listener add the STI map and the `videos` association
at runtime, so no extra mapping is needed.

### 5. Install assets

```bash
bin/console assets:install
```

This publishes the small dependency-free JavaScript controller that powers the adaptive entry
form (toggling the kind-specific field, collection add/remove and drag reordering).

## Usage

- **Admin:** open any product's edit page and switch to the **Videos** tab. Add videos, pick a
  kind (file / url / embed) per row, optionally attach a poster image, and reorder by dragging.
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
  the discriminator keys stay stable because they are derived from the subtype interfaces.
- **Renderer templates:** override at `templates/bundles/SetonoSyliusVideoPlugin/shop/renderer/<type>.html.twig`.
- **Embed sanitizing:** decorate `Setono\SyliusVideoPlugin\Sanitizer\EmbedSanitizerInterface`
  (the default passes the embed code through unchanged — admins are trusted).
- **Uploads:** decorate the `setono_sylius_video.uploader` service, or point `filesystem.adapter`
  at any Flysystem/Gaufrette adapter Sylius exposes.

## Extending — adding a new video kind

Worked example: a `youtube` kind that reuses the `url` column (parse the id from it) and computes
its thumbnail from the YouTube CDN. Because it reuses an existing column **no migration is
needed**.

**1. Subtype model + interface**

```php
namespace App\Entity\Video;

use Setono\SyliusVideoPlugin\Model\ProductVideo;
use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;

interface YoutubeVideoInterface extends ProductVideoInterface
{
    public function getUrl(): ?string;
    public function setUrl(?string $url): void;
    public function getVideoId(): ?string;
}

class YoutubeVideo extends ProductVideo implements YoutubeVideoInterface
{
    protected ?string $url = null;

    public function getType(): string { return 'youtube'; }
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

**3. Discriminator + resource** — register the subtype as a resource so the discriminator listener
picks it up, and teach the listener the `interface → 'youtube'` mapping (decorate
`ProductVideoDiscriminatorMapListener`):

```yaml
setono_sylius_video:
    resources:
        youtube_video:
            classes:
                model: App\Entity\Video\YoutubeVideo
                interface: App\Entity\Video\YoutubeVideoInterface
```

**4. Renderer + poster** — implement `VideoRendererInterface` (`instanceof YoutubeVideoInterface`),
tag it `setono_sylius_video.renderer`, and add a `shop/renderer/youtube.html.twig` template. Add a
poster resolver that builds the thumbnail from the parsed id and tag it
`setono_sylius_video.poster_resolver`:

```php
final class YoutubePosterResolver implements VideoPosterResolverInterface
{
    public function supports(ProductVideoInterface $video): bool
    {
        return $video instanceof YoutubeVideoInterface && null !== $video->getVideoId();
    }

    public function resolve(ProductVideoInterface $video): ?string
    {
        \assert($video instanceof YoutubeVideoInterface);

        return null === $video->getVideoId()
            ? null
            : sprintf('https://img.youtube.com/vi/%s/hqdefault.jpg', $video->getVideoId());
    }
}
```

**5. Form** — add the kind to the `VideoKindRegistry` (label + factory + field name); reuse the
adaptive entry form. A kind reusing the `url` field renders the existing URL widget automatically.

**6. Validation + translations** — add a per-subtype validation file and the kind's label /
message keys to the `messages` translation files.

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
