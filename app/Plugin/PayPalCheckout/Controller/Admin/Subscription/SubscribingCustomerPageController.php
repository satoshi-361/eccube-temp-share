<?php

namespace Plugin\PayPalCheckout\Controller\Admin\Subscription;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Order;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\PaginatorInterface;
use Plugin\PayPalCheckout\Form\Type\Admin\Subscription\SubscribingCustomerConditionType;
use Plugin\PayPalCheckout\Repository\SubscribingCustomerRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SubscribingCustomerPageController
 * @package Plugin\PayPalCheckout\Controller\Admin\Subscription
 */
class SubscribingCustomerPageController extends AbstractController
{
    /** @var SubscribingCustomerRepository */
    private $subscribingCustomerRepository;

    /**
     * SubscribingCustomerController constructor.
     * @param SubscribingCustomerRepository $subscribingCustomerRepository
     */
    public function __construct(
        SubscribingCustomerRepository $subscribingCustomerRepository
    ) {
        $this->subscribingCustomerRepository = $subscribingCustomerRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/paypal/subscribing_customer", name="paypal_admin_subscribing_customer")
     * @Route("/%eccube_admin_route%/paypal/subscribing_customer/{page_no}", requirements={"page_no" = "\d+"}, name="paypal_admin_subscribing_customer_pageno")
     * @Template("@PayPalCheckout/admin/subscribing_customer.twig")
     * @param Request $request
     * @param int $page_no
     * @param PaginatorInterface $paginator
     * @return array
     */
    public function index(Request $request, $page_no = null, PaginatorInterface $paginator)
    {
        $page_no = $page_no ?? 1;

        $this->doProcessErrors(function (array $errors) {
            foreach ($errors as $subscribingCustomerId => $error) {
                $SubscribingCustomer = $this->subscribingCustomerRepository->find($subscribingCustomerId);
                $SubscribingCustomer->setErrorMessage($error);
                $this->entityManager->persist($SubscribingCustomer);
            }
            $this->entityManager->flush();
        });

        $builder = $this->formFactory->createBuilder(SubscribingCustomerConditionType::class);
        $searchForm = $builder->getForm();

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);
            /**
             * 検索が実行された場合は, セッションに検索条件を保存する.
             * ページ番号は最初のページ番号に初期化する.
             */
            $page_no = 1;
            $searchData = $searchForm->getData();
            // 検索条件, ページ番号をセッションに保持.
            $this->session->set('eccube.paypal.admin.subscribing_customer.search', FormUtil::getViewData($searchForm));
            $this->session->set('eccube.paypal.admin.subscribing_customer.page_no', $page_no);
        } else {
            if (null !== $page_no) {
                if ($page_no) {
                    // ページ送りで遷移した場合.
                    $this->session->set('eccube.paypal.admin.subscribing_customer.page_no', (int) $page_no);
                } else {
                    // 他画面から遷移した場合.
                    $page_no = $this->session->get('eccube.paypal.admin.subscribing_customer.page_no', 1);
                }
                $viewData = $this->session->get('eccube.paypal.admin.subscribing_customer.search', []);
                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
            } else {
                /**
                 * 初期表示の場合.
                 */
                $page_no = 1;
                // submit default value
                $viewData = FormUtil::getViewData($searchForm);
                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);

                // セッション中の検索条件, ページ番号を初期化.
                $this->session->set('eccube.paypal.admin.subscribing_customer.search', $viewData);
                $this->session->set('eccube.paypal.admin.subscribing_customer.page_no', $page_no);
            }
        }

        $qb = $this->subscribingCustomerRepository->getQueryBuilderBySearchDataForAdmin($searchData);
        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            10
        );

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'has_errors' => false,
        ];
    }

    private function createQueryBuilder(array $searchData)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('o')
            ->from(Order::class, 'o')
            ->innerJoin('', '')
            ->orderBy('o.order_date', 'DESC')
            ->addOrderBy('o.id', 'DESC');

        if (!empty($searchData['OrderStatuses']) && count($searchData['OrderStatuses']) > 0) {
            $qb->andWhere($qb->expr()->in('o.OrderStatus', ':OrderStatuses'))
                ->setParameter('OrderStatuses', $searchData['OrderStatuses']);
        }

        if (!empty($searchData['PaymentType']) && count($searchData['PaymentType']) > 0) {
            $qb->andWhere($qb->expr()->in('o.Payment', ':PaymentType'))
                ->setParameter('PaymentType', $searchData['PaymentType']);
        }
        return $qb;
    }

    /**
     * @param callable $callback
     * @return void
     */
    public function doProcessErrors(callable $callback): void
    {
        try {
            /** @var array $errors */
            $errors = $this->session->get('paypal.errors', null);
            if (is_null($errors)) return;
            call_user_func($callback, $errors);
        } finally {
            $this->session->remove('paypal.errors');
        }
    }
}
