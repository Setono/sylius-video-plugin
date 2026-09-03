<?php

declare(strict_types=1);

namespace Setono\SyliusVideoPlugin\Tests\Application\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Setono\SyliusVideoPlugin\Model\ProductVideosAwareInterface;
use Setono\SyliusVideoPlugin\Model\ProductVideosAwareTrait;
use Sylius\Component\Core\Model\Product as BaseProduct;

/**
 * Attribute-mapped like a Sylius-Standard entity: the `videos` association comes from the trait's
 * own attributes, exactly the step the README asks adopting applications to perform.
 */
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
