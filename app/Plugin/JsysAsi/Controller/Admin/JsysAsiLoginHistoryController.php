<?php
namespace Plugin\JsysAsi\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Knp\Component\Pager\PaginatorInterface;
use Eccube\Controller\AbstractController;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Util\FormUtil;
use Plugin\JsysAsi\Repository\JsysAsiLoginHistoryRepository;
use Plugin\JsysAsi\Form\Type\Admin\JsysAsiLoginHistorySearchType;


/**
 * 管理 > ログイン管理 > ログイン履歴 Controller
 * @author manabe
 *
 */
class JsysAsiLoginHistoryController extends AbstractController
{
    /**
     * セッション 検索フォームデータ
     * @var string
     */
    const SESSION_SEARCH = 'jsys_asi.admin.login_history.search';

    /**
     * セッション ページ
     * @var string
     */
    const SESSION_PAGE_NO = 'jsys_asi.admin.login_history.search.page_no';

    /**
     * セッション ページ件数
     * @var string
     */
    const SESSION_PAGE_COUNT = 'jsys_asi.admin.login_history.search.page_count';


    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepo;

    /**
     * @var JsysAsiLoginHistoryRepository
     */
    protected $historyRepo;


    /**
     * JsysAsiLoginHistoryController constructor.
     * @param PageMaxRepository $pageMaxRepo
     * @param JsysAsiLoginHistoryRepository $historyRepo
     */
    public function __construct(
        PageMaxRepository $pageMaxRepo,
        JsysAsiLoginHistoryRepository $historyRepo
    ) {
        $this->pageMaxRepo = $pageMaxRepo;
        $this->historyRepo = $historyRepo;
    }

    /**
     * ログイン履歴
     * @param Request $request
     * @param int|null $page_no
     * @param PaginatorInterface $paginator
     * @return array
     *
     * @Route(
     *   "/%eccube_admin_route%/jsys_asi_login_history/",
     *   name="admin_jsys_asi_login_history"
     * )
     * @Route(
     *   "/%eccube_admin_route%/jsys_asi_login_history/page/{page_no}",
     *   requirements={"page_no" = "\d+"},
     *   name="admin_jsys_asi_login_history_page"
     * )
     * @Template("@JsysAsi/admin/login_history_index.twig")
     */
    public function index(
        Request $request,
        $page_no = null,
        PaginatorInterface $paginator
    ) {
        $pageMaxis  = $this->pageMaxRepo->findAll();
        $pageCount  = $this->getPageCount($request, $pageMaxis);
        $searchForm = $this->formFactory
            ->createBuilder(JsysAsiLoginHistorySearchType::class)
            ->getForm();

        if ('POST' === $request->getMethod()) {
            // 検索フォームからのPOST
            $searchForm->handleRequest($request);
            if ($searchForm->isValid()) {
                // 内容に問題が無ければ検索内容の取得とページ数を初期化
                $searchData = $searchForm->getData();
                $page_no    = 1;
                $this->session->set(self::SESSION_SEARCH, FormUtil::getViewData(
                    $searchForm
                ));
                $this->session->set(self::SESSION_PAGE_NO, $page_no);

            } else {
                // 問題がある場合はデータを空にしてエラーを表示
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis'  => $pageMaxis,
                    'page_no'    => $page_no,
                    'page_count' => $pageCount,
                    'has_errors' => true,
                ];
            }
        } else {
            if (null !== $page_no || $request->get('resume')) {
                if ($page_no) {
                    $this->session->set(self::SESSION_PAGE_NO, (int)$page_no);
                } else {
                    $page_no = $this->session->get(self::SESSION_PAGE_NO, 1);
                }
                $viewData = $this->session->get(self::SESSION_SEARCH, []);
            } else {
                $page_no  = 1;
                $viewData = FormUtil::getViewData($searchForm);
                $this->session->set(self::SESSION_SEARCH, $viewData);
                $this->session->set(self::SESSION_PAGE_NO, $page_no);
            }
            $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
        }

        $qb         = $this->historyRepo->getQueryBuilderBySearchData($searchData);
        $pagination = $paginator->paginate($qb, $page_no, $pageCount);

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis'  => $pageMaxis,
            'page_no'    => $page_no,
            'page_count' => $pageCount,
            'has_errors' => false,
        ];
    }

    /**
     * 選択中またはデフォルトのページ件数を取得します。
     * @param Request $request
     * @param array $pageMaxis
     * @return string
     */
    private function getPageCount(Request $request, array $pageMaxis)
    {
        $pageCount = $this->session->get(
            self::SESSION_PAGE_COUNT,
            $this->eccubeConfig['eccube_default_page_count']
        );

        $pageCountParam = $request->get('page_count');
        if (!$pageCountParam || !is_numeric($pageCountParam)) {
            return $pageCount;
        }

        /** @var \Eccube\Entity\Master\PageMax $pageMax */
        foreach ($pageMaxis as $pageMax) {
            if ($pageCountParam == $pageMax->getName()) {
                $pageCount = $pageMax->getName();
                $this->session->set(self::SESSION_PAGE_COUNT, $pageCount);
                break;
            }
        }
        return $pageCount;
    }

}
