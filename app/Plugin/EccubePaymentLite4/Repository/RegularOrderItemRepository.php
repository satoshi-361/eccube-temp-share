<?php

namespace Plugin\EccubePaymentLite4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\EccubePaymentLite4\Entity\RegularOrderItem;
use Symfony\Bridge\Doctrine\RegistryInterface;

class RegularOrderItemRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RegularOrderItem::class);
    }
}
