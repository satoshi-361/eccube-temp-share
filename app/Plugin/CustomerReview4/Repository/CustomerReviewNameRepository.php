<?php

namespace Plugin\CustomerReview4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\CustomerReview4\Entity\CustomerReviewName;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CustomerReviewNameRepository extends AbstractRepository
{
    /**
     * CustomerReviewNameRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CustomerReviewName::class);
    }

    /**
     * @param int $customer_id
     * @return null|CustomerReviewName
     */
    public function get($customer_id)
    {
        return $this->findOneBy(['Customer' => $customer_id]);    }

}
