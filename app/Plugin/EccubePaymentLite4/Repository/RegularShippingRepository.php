<?php

namespace Plugin\EccubePaymentLite4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\EccubePaymentLite4\Entity\RegularShipping;
use Symfony\Bridge\Doctrine\RegistryInterface;

class RegularShippingRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RegularShipping::class);
    }
}
