<?php

namespace Plugin\EccubePaymentLite4\Controller\Front\Mypage;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Customer;
use Eccube\Repository\OrderRepository;
use Knp\Component\Pager\PaginatorInterface;
use Plugin\EccubePaymentLite4\Repository\RegularOrderRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class RegularListController extends AbstractController
{
    /**
     * @var RegularOrderRepository
     */
    private $regularOrderRepository;
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    public function __construct(
        RegularOrderRepository $regularOrderRepository,
        OrderRepository $orderRepository
    ) {
        $this->regularOrderRepository = $regularOrderRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * 定期一覧画面
     *
     * @Route(
     *     "/mypage/eccube_payment_lite/regular/",
     *     name="eccube_payment_lite4_mypage_regular_list"
     * )
     * @Template("@EccubePaymentLite4/default/Mypage/regular_list.twig")
     */
    public function index(Request $request, PaginatorInterface $paginator)
    {
        /** @var Customer $Customer */
        $Customer = $this->getUser();

        $qb = $this->regularOrderRepository->getQueryBuilderByCustomer($Customer);

        $pagination = $paginator->paginate(
            $qb,
            $request->get('pageno', 1),
            $this->eccubeConfig['eccube_search_pmax']
        );

        return [
            'pagination' => $pagination,
        ];
    }
}
