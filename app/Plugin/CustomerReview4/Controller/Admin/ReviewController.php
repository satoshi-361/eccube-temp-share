<?php

namespace Plugin\CustomerReview4\Controller\Admin;

use Doctrine\DBAL\LockMode;
use Plugin\CustomerReview4\Entity\CustomerReviewList;
use Plugin\CustomerReview4\Entity\CustomerReviewTotal;
use Plugin\CustomerReview4\Entity\CustomerReviewStatus;
use Plugin\CustomerReview4\Repository\CustomerReviewConfigRepository;
use Plugin\CustomerReview4\Repository\CustomerReviewListRepository;
use Plugin\CustomerReview4\Repository\CustomerReviewTotalRepository;
use Plugin\CustomerReview4\Form\Type\Admin\SearchReviewType;
use Plugin\CustomerReview4\Form\Type\Admin\ReviewPostType;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Product;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Service\CsvExportService;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Eccube\Service\MailService;

class ReviewController extends AbstractController
{
    /**
     * @var CustomerReviewConfigRepository
     */
    protected $customerReviewConfigRepository;

    /**
     * @var CustomerReviewListRepository
     */
    protected $customerReviewListRepository;

    /**
     * @var CustomerReviewTotalRepository
     */
    protected $customerReviewTotalRepository;

    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /** @var CsvExportService */
    protected $csvExportService;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * ReviewController constructor.
     *
     * @param CustomerReviewConfigRepository $customerReviewConfigRepository
     * @param CustomerReviewListRepository $customerReviewListRepository
     * @param CustomerReviewTotalRepository $customerReviewTotalRepository
     * @param PageMaxRepository $pageMaxRepository
     * @param MailService $mailService
     * @param CsvExportService $csvExportService
     */
    public function __construct(
        CustomerReviewConfigRepository $customerReviewConfigRepository,
        CustomerReviewListRepository $customerReviewListRepository,
        CustomerReviewTotalRepository $customerReviewTotalRepository,
        PageMaxRepository $pageMaxRepository,
        MailService $mailService,
        CsvExportService $csvExportService
    ) {
        $this->customerReviewConfigRepository = $customerReviewConfigRepository;
        $this->customerReviewListRepository = $customerReviewListRepository;
        $this->customerReviewTotalRepository = $customerReviewTotalRepository;
        $this->pageMaxRepository = $pageMaxRepository;
        $this->csvExportService = $csvExportService;
        $this->mailService = $mailService;
    }

    /**
     * レビュー一覧画面.
     *
     * @Route("/%eccube_admin_route%/review", name="admin_review_list")
     * @Route("/%eccube_admin_route%/review/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_review_list_page")
     * @Template("@CustomerReview4/admin/list.twig")
     */
    public function index(Request $request, $page_no = null, PaginatorInterface $paginator)
    {
        $CsvType = [];
        if ($this->customerReviewConfigRepository->get()) {
            $CsvType = $this->customerReviewConfigRepository->get()->getCsvType();
        }
        $builder = $this->formFactory
            ->createBuilder(SearchReviewType::class);

        $searchForm = $builder->getForm();

        $page_count = $this->session->get('eccube.admin.review.search.page_count',
            $this->eccubeConfig->get('eccube_default_page_count'));

        $page_count_param = (int) $request->get('page_count');
        $pageMaxis = $this->pageMaxRepository->findAll();

        if ($page_count_param) {
            foreach ($pageMaxis as $pageMax) {
                if ($page_count_param == $pageMax->getName()) {
                    $page_count = $pageMax->getName();
                    $this->session->set('eccube.admin.review.search.page_count', $page_count);
                    break;
                }
            }
        }

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);

            if ($searchForm->isValid()) {
                /**
                 * 検索が実行された場合は, セッションに検索条件を保存する.
                 * ページ番号は最初のページ番号に初期化する.
                 */
                $page_no = 1;
                $searchData = $searchForm->getData();

                // 検索条件, ページ番号をセッションに保持.
                $this->session->set('eccube.admin.review.search', FormUtil::getViewData($searchForm));
                $this->session->set('eccube.admin.review.search.page_no', $page_no);
            } else {
                // 検索エラーの際は, 詳細検索枠を開いてエラー表示する.
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $page_count,
                    'CsvType' => $CsvType,
                    'has_errors' => true,
                ];
            }
        } else {
            if (null !== $page_no || $request->get('resume')) {
                /*
                 * ページ送りの場合または、他画面から戻ってきた場合は, セッションから検索条件を復旧する.
                 */
                if ($page_no) {
                    // ページ送りで遷移した場合.
                    $this->session->set('eccube.admin.review.search.page_no', (int) $page_no);
                } else {
                    // 他画面から遷移した場合.
                    $page_no = $this->session->get('eccube.admin.review.search.page_no', 1);
                }
                $viewData = $this->session->get('eccube.admin.review.search', []);
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
                $this->session->set('eccube.admin.review.search', $viewData);
                $this->session->set('eccube.admin.review.search.page_no', $page_no);
            }
        }

        $qb = $this->customerReviewListRepository->getQueryBuilderBySearchDataForAdmin($searchData);

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
            'CsvType' => $CsvType,
            'has_errors' => false,
        ];
    }

    /**
     * レビュー編集画面.
     *
     * @Route("/%eccube_admin_route%/review/{id}/edit", name="admin_review_edit")
     * @Template("@CustomerReview4/admin/edit.twig")
     */
    public function edit(Request $request, CustomerReviewList $review)
    {
        $Product = $review->getProduct();
        $Customer = $review->getCustomer();

        $page_no = $this->session->get('eccube.admin.review.search.page_no', 1);
        if ( !$Product ) {
            $this->addError('customer_review4.admin.product.not_found', 'admin');
            return $this->redirectToRoute('admin_review_list_page', ['page_no' => $page_no]);
        }

        $form = $this->createForm(ReviewPostType::class, $review);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $review = $form->getData();
            $config = $this->customerReviewConfigRepository->get();
            $grantPoint = $config->getGrantPoint();
            $isGrantPoint = !$Customer ? false : ($review->isGrantPoint() || $review->getStatus()->getId() != CustomerReviewStatus::SHOW ? false : ( $config->isGrantPointPurchase() ? $review->isPurchase() : true ));
            if ( $isGrantPoint ) {
                $review->setGrantPoint(true);
            }

            $this->entityManager->getConnection()->beginTransaction();
            try {
                $this->entityManager->persist($review);
                $this->entityManager->flush($review);
                $this->calcReccomendLevel($Product);
                if ( $isGrantPoint ) {
                    $this->entityManager->lock($Customer, LockMode::PESSIMISTIC_WRITE);
                    $Customer->setPoint( $Customer->getPoint() + $grantPoint);
                    $this->entityManager->flush($Customer);
                }
                $this->addSuccess('customer_review4.admin.save.complete', 'admin');
                $this->entityManager->getConnection()->commit();
            } catch (\Exception $e) {
                $this->addError('customer_review4.admin.save.error', 'admin');
                $this->entityManager->getConnection()->rollback();
            }
            // $this->mailService->sendReviewPointMail($Customer, $grantPoint);

            return $this->redirectToRoute('admin_review_edit', ['id' => $review->getId()]);
        }

        return [
            'form' => $form->createView(),
            'Product' => $Product,
            'Customer' => $Customer,
            'Review' => $review,
        ];
    }

    /** 
     * レビューの集計
     *
     * @param Product $Product
    **/
    public function calcReccomendLevel( Product $Product )
    {
        // 各お勧めレベルの承認者の数を集計
        $qb = $this->entityManager->createQueryBuilder()
                ->Select( 'SUM( CASE WHEN rml.recommend_level = 1 THEN 1 ELSE 0 END ) as level_1' )
                ->addSelect( 'SUM( CASE WHEN rml.recommend_level = 2 THEN 1 ELSE 0 END ) as level_2' )
                ->addSelect( 'SUM( CASE WHEN rml.recommend_level = 3 THEN 1 ELSE 0 END ) as level_3' )
                ->addSelect( 'SUM( CASE WHEN rml.recommend_level = 4 THEN 1 ELSE 0 END ) as level_4' )
                ->addSelect( 'SUM( CASE WHEN rml.recommend_level = 5 THEN 1 ELSE 0 END ) as level_5' )
                ->from('Plugin\\CustomerReview4\\Entity\\CustomerReviewList', 'rml' )
                ->where('rml.Product = :product_id AND rml.Status = :status')
                ->setParameter('product_id', $Product->getId())
                ->setParameter('status', CustomerReviewStatus::SHOW);

        $res = $qb
            ->getQuery()
            ->getSingleResult();

        // 平均値とレビュアー数を求める
        $reviewer_num = 0;
        $recommend_total = 0;
        foreach( $res as $key => $val )
        {
            if ( is_null($res[$key]) ) $res[$key] = 0;
            else $res[$key] = intval($res[$key]);

            $star = intval(substr($key, -1, 1 ));
            $reviewer_num += $res[$key];
            $recommend_total += $star * $res[$key];
        }

        // 書き込み or 削除
        $ReviewTotal = $this->customerReviewTotalRepository->get($Product->getId());
        if ( $recommend_total ) {
            if ( is_null($ReviewTotal) ) {
                $ReviewTotal = new CustomerReviewTotal();
            }
            $ReviewTotal->setProduct($Product);
            $ReviewTotal->setRecommend1($res['level_1']);
            $ReviewTotal->setRecommend2($res['level_2']);
            $ReviewTotal->setRecommend3($res['level_3']);
            $ReviewTotal->setRecommend4($res['level_4']);
            $ReviewTotal->setRecommend5($res['level_5']);
            $ReviewTotal->setRecommendAvg( intval(round($recommend_total * 1000 / $reviewer_num)) );
            $ReviewTotal->setReviewerNum($reviewer_num);
            $this->customerReviewTotalRepository->save($ReviewTotal);
        } else if ( !is_null($ReviewTotal) ) {
            $this->customerReviewTotalRepository->delete($ReviewTotal);
        }
        $this->entityManager->flush($ReviewTotal);
    }

    /**
     * レビューCSVの出力.
     *
     * @Route("%eccube_admin_route%/review/download", name="admin_review_download")
     *
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function download(Request $request)
    {
        // タイムアウトを無効にする.
        set_time_limit(0);

        // sql loggerを無効にする.
        $em = $this->entityManager;
        $em->getConfiguration()->setSQLLogger(null);
        $response = new StreamedResponse();
        $response->setCallback(function () use ($request) {
            $CsvType = [];
            /** @var ProductReviewConfig $Config */
            if ($this->customerReviewConfigRepository->get())
                $CsvType = $this->customerReviewConfigRepository->get()->getCsvType();

            /* @var $csvService CsvExportService */
            $csvService = $this->csvExportService;

            /* @var $repo customerReviewListRepository */
            $repo = $this->customerReviewListRepository;

            // CSV種別を元に初期化.
            $csvService->initCsvType($CsvType);

            // ヘッダ行の出力.
            $csvService->exportHeader();

            $searchForm = $this->createForm(SearchReviewType::class);

            $viewData = $this->session->get('eccube.admin.review.search', []);
            $searchData = FormUtil::submitAndGetData($searchForm, $viewData);

            $qb = $repo->getQueryBuilderBySearchDataForAdmin($searchData);

            // データ行の出力.
            $csvService->setExportQueryBuilder($qb);
            $csvService->exportData(function ($entity, CsvExportService $csvService) {
                $Product = $entity->getProduct();
                $arrCsv = $csvService->getCsvs();
                $row = [];
                // CSV出力項目と合致するデータを取得.
                foreach ($arrCsv as $csv) {
                    // 受注データを検索.
                    $data = $csvService->getData($csv, $entity);
                    if ( $csv->getFieldName() == "purchase" && $data == 1 )
                    {
                        $data = "○";
                    } else if ( $csv->getFieldName() == "recommend_level" )
                    {
                        $star = $data;
                        $data = "";
                        for( $i=0; $i<$star;$i++) $data .= "★";
                    } if ( is_null($data) ) {
                        $data = $csvService->getData($csv, $Product);
                    }
                    $row[] = $data;
                }
                // 出力.
                $csvService->fputcsv($row);
            });
        });

        $now = new \DateTime();
        $filename = 'customer_review_'.$now->format('YmdHis').'.csv';
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename='.$filename);
        $response->send();

        log_info('カスタマーレビューCSV出力ファイル名', [$filename]);

        return $response;
    }
}