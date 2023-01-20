<?php

namespace Plugin\CustomerReview4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\CustomerReview4\Entity\CustomerReviewTotal;
use Symfony\Bridge\Doctrine\RegistryInterface;

class CustomerReviewTotalRepository extends AbstractRepository
{
    /**
     * CustomerReviewTotalRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CustomerReviewTotal::class);
    }

    /**
     * @param int $product_id
     * @return null|CustomerReviewTotal
     */
    public function get($product_id)
    {
        return $this->findOneBy(['Product' => $product_id]);
    }

    public function getRecommend($product_id)
    {
        $res = array( 0, 0, 0, 0, 0 );
        $recommends = $this->get($product_id);
        if ( $recommends ) {
            $res = array( $recommends->getRecommend5(),
                        $recommends->getRecommend4(),
                        $recommends->getRecommend3(),
                        $recommends->getRecommend2(),
                        $recommends->getRecommend1() );
        }
        return $res;
    }
}