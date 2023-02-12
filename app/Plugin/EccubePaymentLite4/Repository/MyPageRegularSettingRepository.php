<?php

namespace Plugin\EccubePaymentLite4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\EccubePaymentLite4\Entity\MyPageRegularSetting;
use Symfony\Bridge\Doctrine\RegistryInterface;

class MyPageRegularSettingRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MyPageRegularSetting::class);
    }
}
