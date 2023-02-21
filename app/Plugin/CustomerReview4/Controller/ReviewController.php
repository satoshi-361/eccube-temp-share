<?php

namespace Plugin\CustomerReview4\Controller;

use Plugin\CustomerReview4\Repository\CustomerReviewConfigRepository;
use Plugin\CustomerReview4\Repository\CustomerReviewListRepository;
use Plugin\CustomerReview4\Form\Type\SearchReviewType;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Product;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\ProductRepository;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ReviewController extends AbstractController
{
    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var CustomerReviewConfigRepository
     */
    protected $customerReviewConfigRepository;

    /**
     * @var CustomerReviewListRepository
     */
    protected $customerReviewListRepository;

    /**
     * ReviewController constructor.
     *
     * @param BaseInfoRepository $baseInfoRepository
     * @param ProductRepository $productRepository
     * @param CustomerReviewConfigRepository $customerReviewConfigRepository
     * @param CustomerReviewListRepository $customerReviewListRepository
     */
    public function __construct(
        BaseInfoRepository $baseInfoRepository,
        ProductRepository $productRepository,
        CustomerReviewConfigRepository $customerReviewConfigRepository,
        CustomerReviewListRepository $customerReviewListRepository
    ) {
        $this->BaseInfo = $baseInfoRepository->get();
        $this->productRepository = $productRepository;
        $this->customerReviewConfigRepository = $customerReviewConfigRepository;
        $this->customerReviewListRepository = $customerReviewListRepository;
    }

    /**
     * レビュー一覧画面.
     *
     * @Route("/review/list/{id}", name="review_list", requirements={"id" = "\d+"})
     * @Template("@CustomerReview4/list.twig")
     * @ParamConverter("Product", options={"repository_method" = "findWithSortedClassCategories"})
     */
    public function index(Request $request, Product $Product, PaginatorInterface $paginator)
    {
        $Config = $this->customerReviewConfigRepository->get();
        $ViewMax = $Config->getReviewMax();

        if (!$this->checkVisibility($Product)) {
            throw new NotFoundHttpException();
        }

        $builder = $this->formFactory->createNamedBuilder('', SearchReviewType::class);
        
        if ($request->getMethod() === 'GET') {
            $builder->setMethod('GET');
        }

        $searchForm = $builder->getForm();
        $searchForm->handleRequest($request);

        $searchData = $searchForm->getData();
        $searchData['product_id']  = $Product->getId();
        $qb = $this->customerReviewListRepository->getQueryBuilderBySearchData($searchData);

        $query = $qb->getQuery()
            ->useResultCache(true, $this->eccubeConfig['eccube_result_cache_lifetime_short']);

        $pagination = $paginator->paginate(
            $query,
            !empty($searchData['pageno']) ? $searchData['pageno'] : 1,
            $ViewMax ? $ViewMax : 4294967295
        );

        return [
            'Product' => $Product,
            'pagination' => $pagination,
            'search_form' => $searchForm->createView(),
            'review_config' => $Config,
        ];

    }

    /**
     * 閲覧可能な商品かどうかを判定
     *
     * @param Product $Product
     *
     * @return boolean 閲覧可能な場合はtrue
     */
    private function checkVisibility(Product $Product)
    {
        $is_admin = $this->session->has('_security_admin');

        // 管理ユーザの場合はステータスやオプションにかかわらず閲覧可能.
        if (!$is_admin) {
            // 在庫なし商品の非表示オプションが有効な場合.
            if ($this->BaseInfo->isOptionNostockHidden()) {
                if (!$Product->getStockFind()) {
                    return false;
                }
            }
            // 公開ステータスでない商品は表示しない.
            if ($Product->getStatus()->getId() !== ProductStatus::DISPLAY_SHOW) {
                return false;
            }
        }

        return true;
    }
}