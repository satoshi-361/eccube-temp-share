<?php
/*
 * Copyright(c) 2020 YAMATO.CO.LTD
 */
namespace Plugin\SitemapXmlGenerator\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Eccube\Common\EccubeConfig;
use Eccube\Doctrine\Query\Queries;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Repository\AbstractRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\QueryKey;
use Eccube\Util\StringUtil;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Plugin\SitemapXmlGenerator\Entity\SitemapSetting;

/**
 * SitemapXmlRepository
 * 
 * @author Masaki Okada
 */
class SitemapXmlRepository extends AbstractRepository
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;
    
    /**
     * コンストラクタ
     *
     * @param RegistryInterface $registry
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
        RegistryInterface $registry,
        EccubeConfig $eccubeConfig
        ) {
            parent::__construct($registry, SitemapSetting::class);
            $this->eccubeConfig = $eccubeConfig;
    }

    
    /**
     * Flushes all changes to objects that have been queued up to now to the database.
     * This effectively synchronizes the in-memory state of managed objects with the
     * database.
     *
     * If an entity is explicitly passed to this method only this entity and
     * the cascade-persist semantics + scheduled inserts/removals are synchronized.
     *
     * @param null|object|array $entity
     *
     * @return void
     *
     * @throws \Doctrine\ORM\OptimisticLockException If a version check on an entity that
     *         makes use of optimistic locking fails.
     * @throws ORMException
     */
    public function flush($entity) {
        $this->getEntityManager()->flush($entity);
    }
}
