<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Form\Extension;

use Setono\SyliusVideoPlugin\Model\EmbedProductVideo;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;

final class EmbedProductVideoTypeExtension extends AbstractProductVideoTypeExtension
{
    protected function getType(): string
    {
        return EmbedProductVideo::getType();
    }

    protected function fieldNames(): array
    {
        return ['html'];
    }

    protected function addFields(FormInterface $form): void
    {
        if ($form->has('html')) {
            return;
        }

        $form->add('html', TextareaType::class, [
            'label' => 'setono_sylius_video.form.video.html',
            'help' => 'setono_sylius_video.form.video.help.html',
            'required' => false,
            'attr' => ['data-video-fields' => $this->getType(), 'rows' => 4],
        ]);
    }
}
