<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Form\Extension;

use Setono\SyliusVideoPlugin\Model\UrlProductVideo;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormInterface;

final class UrlProductVideoTypeExtension extends AbstractProductVideoTypeExtension
{
    protected function getType(): string
    {
        return UrlProductVideo::getType();
    }

    protected function fieldNames(): array
    {
        return ['url'];
    }

    protected function addFields(FormInterface $form): void
    {
        if ($form->has('url')) {
            return;
        }

        $form->add('url', UrlType::class, [
            'label' => 'setono_sylius_video.form.video.url',
            'help' => 'setono_sylius_video.form.video.help.url',
            'required' => false,
            'default_protocol' => 'https',
            'attr' => ['data-video-fields' => $this->getType()],
        ]);
    }
}
