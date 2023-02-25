<?php

namespace Plugin\CMBlogPro\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Eccube\Doctrine\Query\Queries;
use Eccube\Util\StringUtil;
use Eccube\Repository\AbstractRepository;
use Plugin\CMBlogPro\Entity\Blog;
use Plugin\CMBlogPro\Repository\ConfigRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * BlogRepository
 */
class BlogRepository extends AbstractRepository
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * ConfigRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(
        RegistryInterface $registry,
        Queries $queries,
        ConfigRepository $configRepository)
    {
        parent::__construct($registry, Blog::class);
        $this->queries = $queries;
        $this->configRepository = $configRepository;
    }

    /**
     * @param int $id
     *
     * @return null|Blog
     */
    public function get($id = 1)
    {
        return $this->find($id);
    }

    /**
     * @return Blog[]|ArrayCollection
     */
    public function getList()
    {
        $config = $this->configRepository->get();
        $currentDate = new \DateTime();
        return $this
            ->createQueryBuilder('o')->select('o')
            ->andWhere('o.release_date < :date OR o.release_date is NULL AND o.create_date < :date')
            ->setParameter('date', $currentDate)
            ->andWhere('o.Status = 1')
            ->setMaxResults($config->getDisplayBlock())
            ->orderBy('o.create_date', 'DESC')
            ->getQuery()->getResult();
    }

    /**
     * get query builder.
     *
     * @param  array $searchData
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderBySearchData($searchData)
    {
        $currentDate = new \DateTime();
        $qb = $this->createQueryBuilder('o')->select('o')
        ->andWhere('o.release_date < :date OR o.release_date is NULL AND o.create_date < :date')
        ->setParameter('date', $currentDate)
        ->andWhere('o.Status = 1');

        // カテゴリ
        if (isset($searchData['categories']) && StringUtil::isNotBlank($searchData['categories'])) {
            $qb
                ->innerJoin('o.BlogCategories', 'bct')
                ->innerJoin('bct.Category', 'c')
                ->andWhere($qb->expr()->in('bct.Category', ':categories'))
                ->setParameter('categories', $searchData['categories']);
        }

        // タグで検索
        if (isset($searchData['tag']) && StringUtil::isNotBlank($searchData['tag'])) {
            $qb
                ->andWhere('o.tag LIKE :tag')
                ->setParameter('tag', '%'. $searchData['tag'] .'%');
        }

        // タグで検索
        if (isset($searchData['date']) && StringUtil::isNotBlank($searchData['date'])) {
            
            $currentDate = new \DateTime();
            $qb
                ->andWhere('o.release_date >= :max_date AND o.release_date <= :min_date AND o.release_date <= :currentDate')
                ->setParameter('max_date', date('Y-m-01 00:00:00', strtotime($searchData['date'])))
                ->setParameter('min_date', date('Y-m-t 23:59:59', strtotime($searchData['date'])))
                ->setParameter('currentDate', $currentDate);

        }

        // Order By
        $qb->orderBy('o.create_date', 'DESC');

        return $this->queries->customize('Blog.getQueryBuilderBySearchData', $qb, $searchData);
    }

    public function getQueryBuilderBySearchDataAdmin($searchData)
    {
        $qb = $this->createQueryBuilder('o')->select('o');

        // id/タイトル
        if (isset($searchData['id']) && StringUtil::isNotBlank($searchData['id'])) {
            $id = preg_match('/^\d{0,10}$/', $searchData['id']) ? $searchData['id'] : null;
            $qb
                ->andWhere('o.id = :id OR o.title LIKE :likeid')
                ->setParameter('id', $id)
                ->setParameter('likeid', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $searchData['id']).'%');
        }

        // ステータス
        if (!empty($searchData['status']) && $searchData['status']) {
            $qb
                ->andWhere($qb->expr()->in('o.Status', ':Status'))
                ->setParameter('Status', $searchData['status']);
        }        

        // リリース日付
        if (!empty($searchData['releaseStart']) && $searchData['releaseStart']) {
            $date = $searchData['releaseStart'];
            $qb
                ->andWhere('o.release_date >= :releaseStart')
                ->setParameter('releaseStart', $date);
        }

        if (!empty($searchData['releaseEnd']) && $searchData['releaseEnd']) {
            $date = clone $searchData['releaseEnd'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('o.release_date < :releaseEnd')
                ->setParameter('releaseEnd', $date);
        }

        // create_date
        if (!empty($searchData['create_date_start']) && $searchData['create_date_start']) {
            $date = $searchData['create_date_start'];
            $qb
                ->andWhere('o.create_date >= :create_date_start')
                ->setParameter('create_date_start', $date);
        }

        if (!empty($searchData['create_date_end']) && $searchData['create_date_end']) {
            $date = clone $searchData['create_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('o.create_date < :create_date_end')
                ->setParameter('create_date_end', $date);
        }

        // update_date
        if (!empty($searchData['update_date_start']) && $searchData['update_date_start']) {
            $date = $searchData['update_date_start'];
            $qb
                ->andWhere('o.update_date >= :update_date_start')
                ->setParameter('update_date_start', $date);
        }
        if (!empty($searchData['update_date_end']) && $searchData['update_date_end']) {
            $date = clone $searchData['update_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('o.update_date < :update_date_end')
                ->setParameter('update_date_end', $date);
        }

        // カテゴリ
        if (!empty($searchData['category_id']) && $searchData['category_id']) {
            // Not a proper category class so there's no such function
            //$Categories = $searchData['category_id']->getSelfAndDescendants();
            
            $Categories = [$searchData['category_id']];
            if ($Categories) {
                $qb
                    ->innerJoin('o.BlogCategories', 'bct')
                    ->innerJoin('bct.Category', 'c')
                    ->andWhere($qb->expr()->in('bct.Category', ':Categories'))
                    ->setParameter('Categories', $Categories);
            }
        }

        // Order By
        $qb->orderBy('o.create_date', 'DESC');

        return $this->queries->customize('Blog.getQueryBuilderBySearchData', $qb, $searchData);
    }

    /**
     * @return Blog[]|ArrayCollection
     */
    public function getReleaseDate($searchData)
    {
        $qb = $this->createQueryBuilder('o');

        // カテゴリ
        if (isset($searchData['categories']) && StringUtil::isNotBlank($searchData['categories'])) {
            $qb
                ->innerJoin('o.BlogCategories', 'bct')
                ->innerJoin('bct.Category', 'c')
                ->andWhere($qb->expr()->in('bct.Category', ':categories'))
                ->setParameter('categories', $searchData['categories']);
        }
        
        $qb
            ->andWhere('o.release_date <= :date OR o.release_date is NULL AND o.create_date <= :date')
            ->setParameter('date', new \DateTime())
            ->andWhere('o.Status = 1')
            ->orderBy('o.release_date', 'DESC')
            ->select('o.release_date');

        return $qb->getQuery()->getResult();
    }
}