<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Model;

interface EmbedVideoInterface extends ProductVideoInterface
{
    public function getCode(): ?string;

    public function setCode(?string $code): void;
}
