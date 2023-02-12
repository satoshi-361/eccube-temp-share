<?php

namespace Plugin\EccubePaymentLite4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\EccubePaymentLite4\Entity\ConvenienceStore;
use Symfony\Bridge\Doctrine\RegistryInterface;

class ConvenienceStoreRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ConvenienceStore::class);
    }
}
