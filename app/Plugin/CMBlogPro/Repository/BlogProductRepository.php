<?php

namespace Plugin\CMBlogPro\Repository;

use Eccube\Doctrine\Query\Queries;
use Eccube\Util\StringUtil;
use Eccube\Repository\AbstractRepository;
use Plugin\CMBlogPro\Entity\BlogProduct;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * BlogProductRepository
 */
class BlogProductRepository extends AbstractRepository
{
    /**
     * BlogProductRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, BlogProduct::class);
    }

}
