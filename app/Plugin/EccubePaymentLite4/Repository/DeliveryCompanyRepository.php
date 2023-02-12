<?php

namespace Plugin\EccubePaymentLite4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\EccubePaymentLite4\Entity\DeliveryCompany;
use Symfony\Bridge\Doctrine\RegistryInterface;

class DeliveryCompanyRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DeliveryCompany::class);
    }
}
