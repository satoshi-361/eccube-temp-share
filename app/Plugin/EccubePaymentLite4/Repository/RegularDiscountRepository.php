<?php

namespace Plugin\EccubePaymentLite4\Repository;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Eccube\Repository\AbstractRepository;
use Plugin\EccubePaymentLite4\Entity\RegularDiscount;
use Symfony\Bridge\Doctrine\RegistryInterface;

class RegularDiscountRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RegularDiscount::class);
    }

    public function getRegularDiscountsChoices(): array
    {
        $groups = [];
        $RegularDiscounts = $this->findAll();

        /** @var RegularDiscount $RegularDiscount */
        foreach ($RegularDiscounts as $RegularDiscount) {
            $discountId = $RegularDiscount->getDiscountId();
            $regularCount = $RegularDiscount->getRegularCount();
            $discountRate = $RegularDiscount->getDiscountRate();

            $text = !empty($groups[$discountId]) ? $groups[$discountId] : '';
            if (empty($text)) {
                $text .= 'ID:'.$discountId.' 初回'.(is_null($discountRate) ? '--' : $discountRate).'%割引 ';
            } else {
                $text .= (is_null($regularCount) ? '--' : $regularCount).'回から'.(is_null($discountRate) ? '--' : $discountRate).'%割引 ';
            }

            $groups[$discountId] = $text;
        }

        return $groups;
    }

    public function getMaxNumberOfRegularCount($discountId, $regularCount): int
    {
        $qb = $this
            ->createQueryBuilder('rd')
            ->select('MAX(rd.regular_count) as max_number_of_regular_count')
            ->where('rd.discount_id = :discountId')
            ->andWhere('rd.regular_count <= :regularCount')
            ->setParameter('discountId', $discountId)
            ->setParameter('regularCount', $regularCount)
        ;
        try {
            return (int) $qb
                ->getQuery()
                ->getSingleResult()['max_number_of_regular_count'];
        } catch (NoResultException | NonUniqueResultException $e) {
            return $regularCount;
        }
    }
}
