<?php

namespace Plugin\EccubePaymentLite4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Symfony\Bridge\Doctrine\RegistryInterface;

class RegularStatusRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RegularStatus::class);
    }
}
