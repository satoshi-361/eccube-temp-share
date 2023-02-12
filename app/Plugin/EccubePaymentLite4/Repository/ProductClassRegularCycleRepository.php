<?php

namespace Plugin\EccubePaymentLite4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\EccubePaymentLite4\Entity\ProductClassRegularCycle;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ProductClassRegularCycleRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ProductClassRegularCycle::class);
    }
}
