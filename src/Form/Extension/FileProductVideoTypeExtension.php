<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Form\Extension;

use Setono\SyliusVideoPlugin\Model\FileProductVideo;
use Symfony\Component\Form\Extension\Core\Type\FileType;

final class FileProductVideoTypeExtension extends AbstractProductVideoTypeExtension
{
    protected function getType(): string
    {
        return FileProductVideo::getType();
    }

    protected function getFields(): array
    {
        return [
            'file' => [FileType::class, [
                'label' => 'setono_sylius_video.form.video.file',
                'help' => 'setono_sylius_video.form.video.help.file',
                'required' => false,
                'attr' => ['data-video-fields' => $this->getType(), 'accept' => 'video/*'],
            ]],
        ];
    }
}
