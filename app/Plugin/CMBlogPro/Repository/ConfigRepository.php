<?php

namespace Plugin\CMBlogPro\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\CMBlogPro\Entity\Config;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * ConfigRepository
 */
class ConfigRepository extends AbstractRepository
{
    /**
     * ConfigRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Config::class);
    }

    /**
     * @param int $id
     *
     * @return null|Config
     */
    public function get($id = 1)
    {
        return $this->find($id);
    }
}
