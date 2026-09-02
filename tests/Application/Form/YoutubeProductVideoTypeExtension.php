<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Application\Form;

use Setono\SyliusVideoPlugin\Form\Extension\AbstractProductVideoTypeExtension;
use Setono\SyliusVideoPlugin\Tests\Application\Entity\Video\YoutubeProductVideo;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

final class YoutubeProductVideoTypeExtension extends AbstractProductVideoTypeExtension
{
    protected function getType(): string
    {
        return YoutubeProductVideo::getType();
    }

    protected function getFields(): array
    {
        // Field names must be unique across types (the plugin's URL type already owns `url`), so
        // this one is named `youtube_url` and mapped onto the inherited `url` property.
        return [
            'youtube_url' => [UrlType::class, [
                'property_path' => 'url',
                'label' => 'app.form.video.youtube_url',
                'help' => 'app.form.video.help.youtube_url',
                'required' => false,
                'default_protocol' => 'https',
                'attr' => ['data-video-fields' => $this->getType()],
            ]],
        ];
    }
}
