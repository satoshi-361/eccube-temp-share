<?php

namespace Plugin\EccubePaymentLite4\Controller\Admin\Regular;

use Eccube\Controller\AbstractController;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\PaginatorInterface;
use Plugin\EccubePaymentLite4\Form\Type\Admin\SearchRegularOrderType;
use Plugin\EccubePaymentLite4\Repository\RegularOrderRepository;
use Plugin\EccubePaymentLite4\Service\IsActiveRegularService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    /**
     * @var PageMaxRepository
     */
    private $pageMaxRepository;
    /**
     * @var RegularOrderRepository
     */
    private $regularOrderRepository;
    /**
     * @var IsActiveRegularService
     */
    private $isActiveRegularService;

    public function __construct(
        PageMaxRepository $pageMaxRepository,
        RegularOrderRepository $regularOrderRepository,
        IsActiveRegularService $isActiveRegularService
    ) {
        $this->pageMaxRepository = $pageMaxRepository;
        $this->regularOrderRepository = $regularOrderRepository;
        $this->isActiveRegularService = $isActiveRegularService;
    }

    /**
     * @Route(
     *     "/%eccube_admin_route%/eccube_payment_lite/regular/index",
     *     name="eccube_payment_lite4_admin_regular_index"
     * )
     * @Route(
     *     "/%eccube_admin_route%/eccube_payment_lite/regular/index/page/{page_no}",
     *     requirements={"page_no" = "\d+"},
     *     name="eccube_payment_lite4_admin_regular_index_page"
     * )
     * @Template("@EccubePaymentLite4/admin/Regular/Order/index.twig")
     */
    public function index(Request $request, $page_no = null, PaginatorInterface $paginator)
    {
        if (!$this->isActiveRegularService->isActive()) {
            throw new NotFoundHttpException();
        }
        $searchForm = $this->createForm(SearchRegularOrderType::class);
        $page_count = $this->session->get('eccube.admin.regular.order.search.page_count',
            $this->eccubeConfig->get('eccube_default_page_count'));

        $page_count_param = (int) $request->get('page_count');
        $pageMaxis = $this->pageMaxRepository->findAll();

        if ($page_count_param) {
            foreach ($pageMaxis as $pageMax) {
                if ($page_count_param == $pageMax->getName()) {
                    $page_count = $pageMax->getName();
                    $this->session->set('eccube.admin.regular.order.search.page_count', $page_count);
                    break;
                }
            }
        }
        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);

            if ($searchForm->isValid()) {
                $page_no = 1;
                $searchData = $searchForm->getData();

                // 検索条件, ページ番号をセッションに保持.
                $this->session->set('eccube.admin.regular.order.search', FormUtil::getViewData($searchForm));
                $this->session->set('eccube.admin.regular.order.search.page_no', $page_no);
            } else {
                // 検索エラーの際は, 詳細検索枠を開いてエラー表示する.
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $page_count,
                    'has_errors' => true,
                ];
            }
        } else {
            if (null !== $page_no || $request->get('resume')) {
                if ($page_no) {
                    $this->session->set('eccube.admin.regular.order.search.page_no', (int) $page_no);
                } else {
                    $page_no = $this->session->get('eccube.admin.regular.order.search.page_no', 1);
                }
                $viewData = $this->session->get('eccube.admin.regular.order.search', []);
                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
            } else {
                $page_no = 1;
                $viewData = [];

                if ($statusId = (int) $request->get('order_status_id')) {
                    $viewData = ['status' => $statusId];
                }

                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);

                // セッション中の検索条件, ページ番号を初期化.
                $this->session->set('eccube.admin.regular.order.search', $viewData);
                $this->session->set('eccube.admin.regular.order.search.page_no', $page_no);
            }
        }

        $qb = $this->regularOrderRepository->getQueryBuilderBySearchDataForAdmin($searchData);

        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $page_count
        );

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $page_count,
            'has_errors' => false,
        ];
    }
}
