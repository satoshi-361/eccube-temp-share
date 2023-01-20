<?php

namespace Plugin\CustomerReview4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\CustomerReview4\Entity\CustomerReviewStatus;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CustomerReviewStatusRepository extends AbstractRepository
{
    /**
     * CustomerReviewStatusRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CustomerReviewStatus::class);
    }
}
