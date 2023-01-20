<?php

namespace Plugin\PayPalCheckout\Repository;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr;
use Eccube\Doctrine\Query\Queries;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Repository\AbstractRepository;
use Eccube\Repository\QueryKey;
use Plugin\PayPalCheckout\Entity\SubscribingCustomer;
use Plugin\PayPalCheckout\Entity\SubscribingCustomerCondition;
use Plugin\PayPalCheckout\Entity\Transaction;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class SubscribingCustomerRepository
 * @package Plugin\PayPalCheckout\Repository
 */
class SubscribingCustomerRepository extends AbstractRepository
{
    /**
     * @var Queries
     */
    private $queries;

    /**
     * SubscribingCustomerRepository constructor.
     * @param RegistryInterface $registry
     * @param Queries $queries
     */
    public function __construct(
        RegistryInterface $registry,
        Queries $queries
    ) {
        parent::__construct($registry, SubscribingCustomer::class);
        $this->queries = $queries;
    }

    /**
     * @param Transaction $transaction
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function agreement(Transaction $transaction): void
    {
        /** @var Order $order */
        $order = $transaction->getOrder();

        /** @var array $items */
        $items = $order->getProductOrderItems();

        /** @var OrderItem $item */
        foreach ($items as $item) {
            /** @var SubscribingCustomer $subscribingCustomer */
            $subscribingCustomer = SubscribingCustomer::create(
                $order->getCustomer(),
                $item->getProductClass(),
                $transaction
            );
            $this->getEntityManager()->persist($subscribingCustomer);
            $this->getEntityManager()->flush($subscribingCustomer);
        }
    }

    /**
     * get query builder.
     *
     * @param SubscribingCustomerCondition $searchCondition
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderBySearchDataForAdmin(SubscribingCustomerCondition $searchCondition)
    {
        $qb = $this->createQueryBuilder('subscribing_customer')
            ->select('subscribing_customer')
            ->innerJoin('Eccube\Entity\Customer', 'customer',
                Expr\Join::WITH, 'customer.id = subscribing_customer.customer_id');

        // 指定されていたら姓と名から検索
        if (!empty($searchCondition->getName())) {
            $qb->andWhere('CONCAT(customer.name01, customer.name02) LIKE :name')
                ->setParameter('name', "%{$searchCondition->getName()}%");
        }
        // 指定されていたらプラン絞り込み
        if (!empty($searchCondition->getPriceCourses())) {
            $qb->andWhere('subscribing_customer.product_class_id = :product_class_id')
                ->setParameter('product_class_id', $searchCondition->getPriceCourses());
        }
        // 指定があったら解約済みのみ表示する
        if (!empty($searchCondition->getIsDeleted())) {
            $qb->andWhere('subscribing_customer.withdrawal_at IS NOT NULL AND subscribing_customer.withdrawal_at < :now')
                ->setParameter('now', new \DateTime('now'));
        }
        // 指定があったら継続決済のみ表示する
        if (!empty($searchCondition->getIsFailed())) {
            $qb->andWhere('subscribing_customer.error_message IS NOT NULL');
        }

        // Order By
        $qb->orderBy('subscribing_customer.id', 'DESC');

        return $this->queries->customize(QueryKey::PRODUCT_SEARCH_ADMIN, $qb, $searchCondition);
    }
}
