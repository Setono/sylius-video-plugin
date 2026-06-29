<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Form\Extension;

use Setono\SyliusVideoPlugin\Model\ProductVideoInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;

final class FileProductVideoTypeExtension extends AbstractProductVideoTypeExtension
{
    protected function getType(): string
    {
        return ProductVideoInterface::TYPE_FILE;
    }

    protected function fieldNames(): array
    {
        return ['file'];
    }

    protected function addFields(FormInterface $form): void
    {
        if ($form->has('file')) {
            return;
        }

        $form->add('file', FileType::class, [
            'label' => 'setono_sylius_video.form.video.file',
            'help' => 'setono_sylius_video.form.video.help.file',
            'required' => false,
            'attr' => ['data-video-fields' => ProductVideoInterface::TYPE_FILE],
        ]);
    }
}
