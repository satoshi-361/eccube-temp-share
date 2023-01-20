<?php

namespace Plugin\CustomerReview4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\CustomerReview4\Entity\CustomerReviewConfig;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CustomerReviewConfigRepository extends AbstractRepository
{
    /**
     * CustomerReviewConfigRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CustomerReviewConfig::class);
    }

    /**
     * @param int $id
     * @return null|CustomerReviewConfig
     */
    public function get($id = 1)
    {
        return $this->find($id);
    }

}
