<?php

namespace Plugin\PayPalCheckout\Controller\Admin\PaymentStatus;

use Eccube\Controller\AbstractController;
use Knp\Component\Pager\PaginatorInterface;
use Plugin\PayPalCheckout\Form\Type\Admin\PaymentStatus\TransactionConditionType;
use Plugin\PayPalCheckout\Repository\TransactionRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TransactionController
 * @package Plugin\PayPalCheckout\Controller\Admin\PaymentStatus
 */
class TransactionController extends AbstractController
{
    /**
     * @var TransactionRepository
     */
    protected $transactionRepository;

    /**
     * PaymentStatusController constructor.
     * @param TransactionRepository $transactionRepository
     */
    public function __construct(
        TransactionRepository $transactionRepository
    ) {
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/paypal/payment_status", name="paypal_admin_payment_status")
     * @Route("/%eccube_admin_route%/paypal/payment_status/{page_no}", requirements={"page_no" = "\d+"}, name="paypal_admin_payment_status_page")
     * @Template("@PayPalCheckout/admin/payment_status.twig")
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @param int $page_no
     * @return array
     */
    public function index(Request $request, $page_no = null, PaginatorInterface $paginator)
    {
        $page_no = $page_no ?? 1;
        $searchForm = $this->createForm(TransactionConditionType::class);
        $qb = $this->transactionRepository->findBy(
            [],
            ['id' => 'DESC']
        );
        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            20
        );

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'has_errors' => false,
        ];
    }
}
