<?php

namespace Plugin\EccubePaymentLite4\Repository;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\Product;
use Eccube\Repository\AbstractRepository;
use Eccube\Util\StringUtil;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularShipping;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Symfony\Bridge\Doctrine\RegistryInterface;

class RegularOrderRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RegularOrder::class);
    }

    public function getQueryBuilderBySearchDataForAdmin($searchData)
    {
        $qb = $this->createQueryBuilder('ro')
            ->select('ro, rs')
            ->addSelect('roi', 'pref', 'c')
            ->leftJoin('ro.RegularOrderItems', 'roi')
            ->leftJoin('ro.Pref', 'pref')
            ->innerJoin('ro.RegularShippings', 'rs')
            ->innerJoin('ro.Customer', 'c')
        ;

        if (isset($searchData['multi']) && StringUtil::isNotBlank($searchData['multi'])) {
            $multi = preg_match('/^\d{0,10}$/', $searchData['multi']) ? $searchData['multi'] : null;
            $qb
                ->andWhere('ro.id = :multi OR ro.name01 LIKE :likemulti OR ro.name02 LIKE :likemulti OR '.
                            'ro.kana01 LIKE :likemulti OR ro.kana02 LIKE :likemulti OR ro.company_name LIKE :likemulti OR '.
                            'ro.order_no LIKE :likemulti OR ro.email LIKE :likemulti OR ro.phone_number LIKE :likemulti')
                ->setParameter('multi', $multi)
                ->setParameter('likemulti', '%'.$searchData['multi'].'%');
        }
        if (isset($searchData['latest_status']) && StringUtil::isNotBlank($searchData['latest_status'])) {
            $qb
                ->andWhere($qb->expr()->in('ro.OrderStatus', ':latest_status'))
                ->setParameter('latest_status', $searchData['latest_status']);
        }
        if (!empty($searchData['regular_status']) && count($searchData['regular_status'])) {
            $qb
                ->andWhere($qb->expr()->in('ro.RegularStatus', ':regular_status'))
                ->setParameter('regular_status', $searchData['regular_status']);
        }
        if (isset($searchData['name']) && StringUtil::isNotBlank($searchData['name'])) {
            $qb
                ->andWhere('CONCAT(ro.name01, ro.name02) LIKE :name')
                ->setParameter('name', '%'.$searchData['name'].'%');
        }
        if (isset($searchData['kana']) && StringUtil::isNotBlank($searchData['kana'])) {
            $qb
                ->andWhere('CONCAT(ro.kana01, ro.kana02) LIKE :kana')
                ->setParameter('kana', '%'.$searchData['kana'].'%');
        }
        if (isset($searchData['company_name']) && StringUtil::isNotBlank($searchData['company_name'])) {
            $qb
                ->andWhere('ro.company_name like :company_name')
                ->setParameter('company_name', '%'.$searchData['company_name'].'%');
        }
        if (isset($searchData['email']) && StringUtil::isNotBlank($searchData['email'])) {
            $qb
                ->andWhere('ro.email like :email')
                ->setParameter('email', '%'.$searchData['email'].'%');
        }
        if (isset($searchData['phone_number']) && StringUtil::isNotBlank($searchData['phone_number'])) {
            $tel = preg_replace('/[^0-9]/ ', '', $searchData['phone_number']);
            $qb
                ->andWhere('ro.phone_number LIKE :phone_number')
                ->setParameter('phone_number', '%'.$tel.'%');
        }
        if (!empty($searchData['sex']) && count($searchData['sex']) > 0) {
            $qb
                ->andWhere($qb->expr()->in('ro.Sex', ':sex'))
                ->setParameter('sex', $searchData['sex']->toArray());
        }
        if (!empty($searchData['first_order_date_start']) && $searchData['first_order_date_start']) {
            $date = $searchData['first_order_date_start'];
            $qb
                ->andWhere('ro.order_date >= :first_order_date_start')
                ->setParameter('first_order_date_start', $date);
        }
        if (!empty($searchData['first_order_date_end']) && $searchData['first_order_date_end']) {
            $date = clone $searchData['first_order_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('ro.order_date < :first_order_date_end')
                ->setParameter('first_order_date_end', $date);
        }
        if (!empty($searchData['next_delivery_date_start']) && $searchData['next_delivery_date_start']) {
            $date = $searchData['next_delivery_date_start'];
            $qb
                ->andWhere('rs.next_delivery_date >= :next_delivery_date_start')
                ->setParameter('next_delivery_date_start', $date);
        }
        if (!empty($searchData['next_delivery_date_end']) && $searchData['next_delivery_date_end']) {
            $date = clone $searchData['next_delivery_date_end'];
            $date = $date
                ->modify('+1 days');
            $qb
                ->andWhere('rs.next_delivery_date < :next_delivery_date_end')
                ->setParameter('next_delivery_date_end', $date);
        }
        if (isset($searchData['regular_order_id']) && StringUtil::isNotBlank($searchData['regular_order_id'])) {
            $qb
                ->andWhere('ro.id = :regular_order_id')
                ->setParameter('regular_order_id', $searchData['regular_order_id']);
        }
        if (isset($searchData['order_no']) && StringUtil::isNotBlank($searchData['order_no'])) {
            $qb
                ->andWhere('ro.order_no = :order_no')
                ->setParameter('order_no', $searchData['order_no']);
        }
        if (isset($searchData['buy_product_id']) && StringUtil::isNotBlank($searchData['buy_product_id'])) {
            $qb
                ->leftJoin('roi.Product', 'p')
                ->andWhere('p.id = :buy_product_id')
                ->setParameter('buy_product_id', $searchData['buy_product_id']);
        }
        if (isset($searchData['payment_total_start']) && StringUtil::isNotBlank($searchData['payment_total_start'])) {
            $qb
                ->andWhere('ro.payment_total >= :payment_total_start')
                ->setParameter('payment_total_start', $searchData['payment_total_start']);
        }
        if (isset($searchData['payment_total_end']) && StringUtil::isNotBlank($searchData['payment_total_end'])) {
            $qb
                ->andWhere('ro.payment_total <= :payment_total_end')
                ->setParameter('payment_total_end', $searchData['payment_total_end']);
        }

        if (isset($searchData['buy_product_name']) && StringUtil::isNotBlank($searchData['buy_product_name'])) {
            $qb
                ->leftJoin('roi.Product', 'p')
                ->andWhere('p.name LIKE :buy_product_name')
                ->setParameter('buy_product_name', '%'.$searchData['buy_product_name'].'%');
        }
        if (isset($searchData['regular_count_start']) && StringUtil::isNotBlank($searchData['regular_count_start'])) {
            $qb
                ->andWhere('ro.regular_order_count >= :regular_count_start')
                ->setParameter('regular_count_start', $searchData['regular_count_start']);
        }
        if (isset($searchData['regular_count_end']) && StringUtil::isNotBlank($searchData['regular_count_end'])) {
            $qb
                ->andWhere('ro.regular_order_count <= :regular_count_end')
                ->setParameter('regular_count_end', $searchData['regular_count_end']);
        }

        if (!empty($searchData['card_change_request_mail_status']) && count($searchData['card_change_request_mail_status'])) {
            // 複数選択時はクエリを追加しない
            if (count($searchData['card_change_request_mail_status']) < 2) {
                if ($searchData['card_change_request_mail_status'][0] === RegularShipping::CARD_CHANGE_REQUEST_MAIL_UNSENT) {
                    $qb
                        ->andWhere('c.card_change_request_mail_send_date IS NULL');
                }
                if ($searchData['card_change_request_mail_status'][0] === RegularShipping::CARD_CHANGE_REQUEST_MAIL_SENT) {
                    $qb
                        ->andWhere('c.card_change_request_mail_send_date IS NOT NULL');
                }
            }
        }
        $qb->orderBy('ro.id', 'DESC');

        return $qb;
    }

    /**
     * 定期受注作成可能となった定期受注を取得
     */
    public function getRegularOrderCanBeCreated(
        int $id,
        $deadLineDateStart,
        $deadLineDateEnd
    ) {
        $qb = $this->createQueryBuilder('ro');
        $qb
            ->select('ro, rs')
            ->addSelect('roi', 'pref')
            ->leftJoin('ro.RegularOrderItems', 'roi')
            ->leftJoin('ro.Pref', 'pref')
            ->innerJoin('ro.RegularShippings', 'rs')
            ->where('ro.id = :id')
            ->setParameter('id', $id)
            ->andWhere(
                $qb->expr()->in('ro.RegularStatus', ':regularStatusIds')
            )
            ->andWhere(
                $qb->expr()->gte('rs.next_delivery_date', ':deadLineDateStart')
            )
            ->andWhere(
                $qb->expr()->lt('rs.next_delivery_date', ':deadLineDateEnd')
            )
            ->setParameter('regularStatusIds', [RegularStatus::CONTINUE, RegularStatus::WAITING_RE_PAYMENT])
            ->setParameter('deadLineDateStart', $deadLineDateStart)
            ->setParameter('deadLineDateEnd', $deadLineDateEnd)
        ;

        $qb->orderBy('ro.update_date', 'DESC');

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function getRegularOrdersForCommand(
        $deadLineDateStart,
        $deadLineDateEnd
    ) {
        $qb = $this->createQueryBuilder('ro');
        $qb
            ->select('ro, rs')
            ->addSelect('roi', 'pref')
            ->leftJoin('ro.RegularOrderItems', 'roi')
            ->leftJoin('ro.Pref', 'pref')
            ->innerJoin('ro.RegularShippings', 'rs')
            ->where(
                $qb->expr()->in('ro.RegularStatus', ':regularStatusIds')
            )
            ->andWhere(
                $qb->expr()->gte('rs.next_delivery_date', ':deadLineDateStart')
            )
            ->andWhere(
                $qb->expr()->lt('rs.next_delivery_date', ':deadLineDateEnd')
            )
            ->setParameter('regularStatusIds', [RegularStatus::CONTINUE, RegularStatus::WAITING_RE_PAYMENT])
            ->setParameter('deadLineDateStart', $deadLineDateStart)
            ->setParameter('deadLineDateEnd', $deadLineDateEnd)
        ;

        $qb->orderBy('ro.update_date', 'DESC');

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function getRegularOrderForDeliveryNotice(
        $deliveryNoticeStartDate,
        $deliveryNoticeEndDate,
        $rePaymentDateStart,
        $rePaymentDateEnd
    ) {
        $qb = $this->createQueryBuilder('ro');
        $qb
            ->select('ro, rs')
            ->addSelect('roi', 'pref')
            ->leftJoin('ro.RegularOrderItems', 'roi')
            ->leftJoin('ro.Pref', 'pref')
            ->innerJoin('ro.RegularShippings', 'rs')
            ->where(
                $qb->expr()->in('ro.RegularStatus', ':regularStatusIds')
            )
            ->andWhere(
                $qb->expr()->gte('rs.next_delivery_date', ':deadLineDateStart')
            )
            ->andWhere(
                $qb->expr()->lt('rs.next_delivery_date', ':deadLineDateEnd')
            )
            ->setParameter('regularStatusIds', [RegularStatus::CONTINUE, RegularStatus::WAITING_RE_PAYMENT])
            ->setParameter('deadLineDateStart', $deliveryNoticeStartDate)
            ->setParameter('deadLineDateEnd', $deliveryNoticeEndDate)
        ;

        $qb->orderBy('ro.update_date', 'DESC');

        return $qb
            ->getQuery()
            ->getResult();
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilderByCustomer(Customer $Customer)
    {
        return $this
            ->createQueryBuilder('ro')
            ->where('ro.Customer = :Customer')
            ->setParameter('Customer', $Customer)
            ->addOrderBy('ro.id', 'DESC');
    }

    public function getNotAbolishedProductClass($productId)
    {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder();

        $qb
            ->select('count(p.id)')
            ->from(Product::class, 'p')
            ->where('p.id = :productId')
            ->setParameter('productId', $productId)
            ->andWhere($qb->expr()->in('p.Status', ':Status'))
            ->setParameter('Status', [ProductStatus::DISPLAY_SHOW, ProductStatus::DISPLAY_HIDE]);

        try {
            return (int) $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            logs('gmo_epsilon')->error($e);

            return 0;
        } catch (NonUniqueResultException $e) {
            logs('gmo_epsilon')->error($e);

            return 0;
        }
    }
}
