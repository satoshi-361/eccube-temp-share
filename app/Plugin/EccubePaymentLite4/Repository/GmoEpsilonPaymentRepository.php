<?php

namespace Plugin\EccubePaymentLite4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\EccubePaymentLite4\Entity\GmoEpsilonPayment;
use Symfony\Bridge\Doctrine\RegistryInterface;

class GmoEpsilonPaymentRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, GmoEpsilonPayment::class);
    }
}
