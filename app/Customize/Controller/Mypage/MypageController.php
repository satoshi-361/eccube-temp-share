<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\Controller\Mypage;

use Eccube\Controller\AbstractController;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Entity\Product;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Exception\CartException;
use Eccube\Form\Type\Front\CustomerLoginType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\CustomerFavoriteProductRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Service\CartService;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\ProductClass;
use Eccube\Entity\ProductImage;
use Eccube\Entity\ProductStock;
use Eccube\Entity\ProductTag;
use Eccube\Entity\ProductCategory;
use Eccube\Form\Type\Admin\ProductType;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Repository\Master\ProductStatusRepository;
use Eccube\Repository\ProductClassRepository;
use Eccube\Repository\ProductImageRepository;
use Eccube\Repository\TagRepository;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Repository\OrderItemRepository;
use Eccube\Service\CsvExportService;
use Eccube\Util\CacheUtil;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Routing\RouterInterface;
use Customize\Entity\TransferHistory;
use Eccube\Entity\Master\OrderItemType;
use Customize\Entity\Master\ProductStatusColor;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MypageController extends AbstractController
{
    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var CustomerFavoriteProductRepository
     */
    protected $customerFavoriteProductRepository;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var PurchaseFlow
     */
    protected $purchaseFlow;
    
    /**
     * @var CsvExportService
     */
    protected $csvExportService;

    /**
     * @var ProductClassRepository
     */
    protected $productClassRepository;

    /**
     * @var ProductImageRepository
     */
    protected $productImageRepository;

    /**
     * @var TaxRuleRepository
     */
    protected $taxRuleRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var ProductStatusRepository
     */
    protected $productStatusRepository;

    /**
     * @var TagRepository
     */
    protected $tagRepository;

    /**
     * @var OrderItemRepository
     */
    protected $orderItemRepository;

    /**
     * MypageController constructor.
     *
     * @param OrderRepository $orderRepository
     * @param CustomerFavoriteProductRepository $customerFavoriteProductRepository
     * @param CartService $cartService
     * @param BaseInfoRepository $baseInfoRepository
     * @param PurchaseFlow $purchaseFlow
     * @param CsvExportService $csvExportService
     * @param ProductClassRepository $productClassRepository
     * @param ProductImageRepository $productImageRepository
     * @param TaxRuleRepository $taxRuleRepository
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     * @param PageMaxRepository $pageMaxRepository
     * @param ProductStatusRepository $productStatusRepository
     * @param TagRepository $tagRepository
     * @param OrderItemRepository $orderItemRepository
     */
    public function __construct(
        OrderRepository $orderRepository,
        CustomerFavoriteProductRepository $customerFavoriteProductRepository,
        CartService $cartService,
        BaseInfoRepository $baseInfoRepository,
        PurchaseFlow $purchaseFlow,
        CsvExportService $csvExportService,
        ProductClassRepository $productClassRepository,
        ProductImageRepository $productImageRepository,
        TaxRuleRepository $taxRuleRepository,
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
        PageMaxRepository $pageMaxRepository,
        ProductStatusRepository $productStatusRepository,
        OrderItemRepository $orderItemRepository,
        TagRepository $tagRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->customerFavoriteProductRepository = $customerFavoriteProductRepository;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->cartService = $cartService;
        $this->purchaseFlow = $purchaseFlow;
        $this->csvExportService = $csvExportService;
        $this->productClassRepository = $productClassRepository;
        $this->productImageRepository = $productImageRepository;
        $this->taxRuleRepository = $taxRuleRepository;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->pageMaxRepository = $pageMaxRepository;
        $this->productStatusRepository = $productStatusRepository;
        $this->tagRepository = $tagRepository;
        $this->orderItemRepository = $orderItemRepository;
    }

    /**
     * ログイン画面.
     *
     * @Route("/mypage/login", name="mypage_login", methods={"GET", "POST"})
     * @Template("Mypage/login.twig")
     */
    public function login(Request $request, AuthenticationUtils $utils)
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            log_info('認証済のためログイン処理をスキップ');

            if ( $this->session->has(\Customize\Controller\ProductController::SESSION_AFFILIATE_BLOG) ) {
                return $this->redirectToRoute('product_detail', [ 'id' => $this->session->get(\Customize\Controller\ProductController::SESSION_AFFILIATE_BLOG) ]);
            }

            return $this->redirectToRoute('mypage');
        }

        /* @var $form \Symfony\Component\Form\FormInterface */
        $builder = $this->formFactory
            ->createNamedBuilder('', CustomerLoginType::class);

        $builder->get('login_memory')->setData((bool) $request->getSession()->get('_security.login_memory'));

        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $Customer = $this->getUser();
            if ($Customer instanceof Customer) {
                $builder->get('login_email')
                    ->setData($Customer->getEmail());
            }
        }

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_MYPAGE_LOGIN_INITIALIZE, $event);

        $form = $builder->getForm();

        return [
            'error' => $utils->getLastAuthenticationError(),
            'form' => $form->createView(),
        ];
    }

    /**
     * マイページ.
     *
     * @Route("/mypage/", name="mypage", methods={"GET"})
     * @Template("Mypage/index.twig")
     */
    public function index(Request $request, PaginatorInterface $paginator)
    {
        $Customer = $this->getUser();

        // 購入処理中/決済処理中ステータスの受注を非表示にする.
        $this->entityManager
            ->getFilters()
            ->enable('incomplete_order_status_hidden');

        // paginator
        $qb = $this->orderRepository->getQueryBuilderByCustomer($Customer);

        $event = new EventArgs(
            [
                'qb' => $qb,
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_MYPAGE_INDEX_SEARCH, $event);

        $pagination = $paginator->paginate(
            $qb,
            $request->get('pageno', 1),
            $this->eccubeConfig['eccube_search_pmax']
        );

        return [
            'pagination' => $pagination,
        ];
    }
    
    /**
     * 記事一覧.
     *
     * @Route("/mypage/blogs", name="mypage_blog_list", methods={"GET"})
     * @Template("Mypage/blog_list.twig")
     */
    public function blogList(Request $request, PaginatorInterface $paginator)
    {
        $Customer = $this->getUser();

        // 購入処理中/決済処理中ステータスの受注を非表示にする.
        $this->entityManager
            ->getFilters()
            ->enable('incomplete_order_status_hidden');

        // paginator
        // $Blogs = $Customer->getBlogs();
        $qb = $this->productRepository->createQueryBuilder('p');
        $qb
            ->select('p')
            ->leftJoin('p.Customer', 'c')
            ->where('c.id = :customer_id')
            ->andWhere($qb->expr()->notIn('p.Status', ':status'))
            ->setParameter('customer_id', $Customer->getId())
            ->setParameter('status', [ProductStatus::DISPLAY_ABOLISHED]);

        $pagination = $paginator->paginate(
            $qb,
            $request->get('pageno', 1),
            $this->eccubeConfig['eccube_search_pmax']
        );

        return [
            'pagination' => $pagination,
        ];
    }
    
    /**
     * 記事登録ページ.
     *
     * @Route("/mypage/blogs/new", name="mypage_blog_new", methods={"GET", "POST"})
     * @Route("/mypage/blogs/{id}/edit", requirements={"id" = "\d+"}, name="mypage_blog_edit", methods={"GET", "POST"})
     * @Template("Mypage/blog_edit.twig")
     */
    public function blogEdit(Request $request, $id = null, RouterInterface $router, CacheUtil $cacheUtil)
    {
        $Customer = $this->getUser();
        $has_class = false;

        if (is_null($id)) {
            $Product = new Product();
            $ProductClass = new ProductClass();
            $ProductStatus = $this->productStatusRepository->find(ProductStatus::DISPLAY_HIDE);
            $Product
                ->addProductClass($ProductClass)
                ->setStatus($ProductStatus)
                ->setLaunchDate(new \DateTime(date('Y-m-d 00:00:00')));
            $ProductClass
                ->setVisible(true)
                ->setStockUnlimited(true)
                ->setProduct($Product);
            $ProductStock = new ProductStock();
            $ProductClass->setProductStock($ProductStock);
            $ProductStock->setProductClass($ProductClass);
        } else {
            $Product = $this->productRepository->findWithSortedClassCategories($id);

            if ($Product->getCustomer()->getId() !== $Customer->getId()) {
                return $this->redirectToRoute('mypage_blog_list');
            }

            $ProductClass = null;
            $ProductStock = null;
            if (!$Product) {
                throw new NotFoundHttpException();
            }
            // 規格無しの商品の場合は、デフォルト規格を表示用に取得する
            $has_class = $Product->hasProductClass();
            if (!$has_class) {
                $ProductClasses = $Product->getProductClasses();
                foreach ($ProductClasses as $pc) {
                    if (!is_null($pc->getClassCategory1())) {
                        continue;
                    }
                    if ($pc->isVisible()) {
                        $ProductClass = $pc;
                        break;
                    }
                }
                if ($this->BaseInfo->isOptionProductTaxRule() && $ProductClass->getTaxRule()) {
                    $ProductClass->setTaxRate($ProductClass->getTaxRule()->getTaxRate());
                }
                $ProductStock = $ProductClass->getProductStock();
            }
        }

        $builder = $this->formFactory
            ->createBuilder(ProductType::class, $Product);

        // 規格あり商品の場合、規格関連情報をFormから除外
        if ($has_class) {
            $builder->remove('class');
        }

        $form = $builder->getForm();

        if (!$has_class) {
            $ProductClass->setStockUnlimited($ProductClass->isStockUnlimited());
            $form['class']->setData($ProductClass);
        }

        // ファイルの登録
        $images = [];
        $ProductImages = $Product->getProductImage();
        foreach ($ProductImages as $ProductImage) {
            $images[] = $ProductImage->getFileName();
        }
        $form['images']->setData($images);

        $categories = [];
        $ProductCategories = $Product->getProductCategories();
        foreach ($ProductCategories as $ProductCategory) {
            /* @var $ProductCategory \Eccube\Entity\ProductCategory */
            $categories[] = $ProductCategory->getCategory();
        }
        $form['Category']->setData($categories);

        $Tags = $Product->getTags();
        $form['Tag']->setData($Tags);

        $freeArea = null;
        $descriptionDetail = null;

        if ('POST' === $request->getMethod()) {
            $freeArea = $request->request->get('admin_product')['free_area'];
            $descriptionDetail = $request->request->get('admin_product')['description_detail'];

            $form->handleRequest($request);
            if ($form->isValid()) {
                log_info('商品登録開始', [$id]);
                $Product = $form->getData();
                $Product->setFreeArea($freeArea);
                $Product->setDescriptionDetail($descriptionDetail);

                if (!$has_class) {
                    $ProductClass = $form['class']->getData();

                    // 個別消費税
                    if ($this->BaseInfo->isOptionProductTaxRule()) {
                        if ($ProductClass->getTaxRate() !== null) {
                            if ($ProductClass->getTaxRule()) {
                                $ProductClass->getTaxRule()->setTaxRate($ProductClass->getTaxRate());
                            } else {
                                $taxrule = $this->taxRuleRepository->newTaxRule();
                                $taxrule->setTaxRate($ProductClass->getTaxRate());
                                $taxrule->setApplyDate(new \DateTime());
                                $taxrule->setProduct($Product);
                                $taxrule->setProductClass($ProductClass);
                                $ProductClass->setTaxRule($taxrule);
                            }

                            $ProductClass->getTaxRule()->setTaxRate($ProductClass->getTaxRate());
                        } else {
                            if ($ProductClass->getTaxRule()) {
                                $this->taxRuleRepository->delete($ProductClass->getTaxRule());
                                $ProductClass->setTaxRule(null);
                            }
                        }
                    }
                    $this->entityManager->persist($ProductClass);

                    // 在庫情報を作成
                    if (!$ProductClass->isStockUnlimited()) {
                        $ProductStock->setStock($ProductClass->getStock());
                    } else {
                        // 在庫無制限時はnullを設定
                        $ProductStock->setStock(null);
                    }
                    $this->entityManager->persist($ProductStock);
                }

                // カテゴリの登録
                // 一度クリア
                /* @var $Product \Eccube\Entity\Product */
                foreach ($Product->getProductCategories() as $ProductCategory) {
                    $Product->removeProductCategory($ProductCategory);
                    $this->entityManager->remove($ProductCategory);
                }

                $productStatusColorRepository = $this->getDoctrine()->getRepository(ProductStatusColor::class);
                $ProductStatus = $form['Status']->getData();

                $Product->setStatusColor($productStatusColorRepository->find($ProductStatus->getId()));
                $Product->setCustomer($Customer);
                $Customer->addBlog($Product);

                $this->entityManager->persist($Customer);
                $this->entityManager->persist($Product);
                $this->entityManager->flush();

                $count = 1;
                $Categories = $form->get('Category')->getData();
                $categoriesIdList = [];
                foreach ($Categories as $Category) {
                    foreach ($Category->getPath() as $ParentCategory) {
                        if (!isset($categoriesIdList[$ParentCategory->getId()])) {
                            $ProductCategory = $this->createProductCategory($Product, $ParentCategory, $count);
                            $this->entityManager->persist($ProductCategory);
                            $count++;
                            /* @var $Product \Eccube\Entity\Product */
                            $Product->addProductCategory($ProductCategory);
                            $categoriesIdList[$ParentCategory->getId()] = true;
                        }
                    }
                    if (!isset($categoriesIdList[$Category->getId()])) {
                        $ProductCategory = $this->createProductCategory($Product, $Category, $count);
                        $this->entityManager->persist($ProductCategory);
                        $count++;
                        /* @var $Product \Eccube\Entity\Product */
                        $Product->addProductCategory($ProductCategory);
                        $categoriesIdList[$Category->getId()] = true;
                    }
                }

                // 画像の登録
                $add_images = $form->get('add_images')->getData();

                if ( count( $add_images ) ) {
                    $fs = new Filesystem();

                    foreach ( $Product->getProductImage() as $ProductImage ) {
                        $Product->removeProductImage($ProductImage);
                        $this->entityManager->remove($ProductImage);
                        $this->entityManager->flush();

                        $fs->remove($this->eccubeConfig['eccube_save_image_dir'].'/'.$ProductImage->getFileName());
                    }

                    foreach ($add_images as $add_image) {
                        $ProductImage = new \Eccube\Entity\ProductImage();
                        $ProductImage
                            ->setFileName($add_image)
                            ->setProduct($Product)
                            ->setSortNo(1);
                        $Product->addProductImage($ProductImage);
                        $this->entityManager->persist($ProductImage);
    
                        // 移動
                        $file = new File($this->eccubeConfig['eccube_temp_image_dir'].'/'.$add_image);
                        $file->move($this->eccubeConfig['eccube_save_image_dir']);
                    }
                }

                // 画像の削除
                $delete_images = $form->get('delete_images')->getData();
                $fs = new Filesystem();
                foreach ($delete_images as $delete_image) {
                    $ProductImage = $this->productImageRepository->findOneBy([
                        'Product' => $Product,
                        'file_name' => $delete_image,
                    ]);

                    if ($ProductImage instanceof ProductImage) {
                        $Product->removeProductImage($ProductImage);
                        $this->entityManager->remove($ProductImage);
                        $this->entityManager->flush();

                        // 他に同じ画像を参照する商品がなければ画像ファイルを削除
                        if (!$this->productImageRepository->findOneBy(['file_name' => $delete_image])) {
                            $fs->remove($this->eccubeConfig['eccube_save_image_dir'].'/'.$delete_image);
                        }
                    } else {
                        // 追加してすぐに削除した画像は、Entityに追加されない
                        $fs->remove($this->eccubeConfig['eccube_temp_image_dir'].'/'.$delete_image);
                    }
                }

                $this->entityManager->flush();

                $sortNos = $request->get('sort_no_images');
                if ($sortNos) {
                    foreach ($sortNos as $sortNo) {
                        list($filename, $sortNo_val) = explode('//', $sortNo);
                        $ProductImage = $this->productImageRepository
                            ->findOneBy([
                                'file_name' => $filename,
                                'Product' => $Product,
                            ]);
                        $ProductImage->setSortNo($sortNo_val);
                        $this->entityManager->persist($ProductImage);
                    }
                }
                $this->entityManager->flush();

                // 商品タグの登録
                // 商品タグを一度クリア
                $ProductTags = $Product->getProductTag();
                foreach ($ProductTags as $ProductTag) {
                    $Product->removeProductTag($ProductTag);
                    $this->entityManager->remove($ProductTag);
                }

                // 商品タグの登録
                $Tags = $form->get('Tag')->getData();
                foreach ($Tags as $Tag) {
                    $ProductTag = new ProductTag();
                    $ProductTag
                        ->setProduct($Product)
                        ->setTag($Tag);
                    $Product->addProductTag($ProductTag);
                    $this->entityManager->persist($ProductTag);
                }

                $Product->setUpdateDate(new \DateTime());
                $this->entityManager->flush();

                log_info('商品登録完了', [$id]);

                switch ( $ProductStatus->getId() ) {
                    case ProductStatus::DISPLAY_SHOW:
                        $this->addSuccess('admin.common.save_complete');
                        break;

                    case ProductStatus::DISPLAY_HIDE:
                        $this->addSuccess('記事の申請ありがとうございます。審査を行いますので、しばらくお待ちください。');
                        break;
                        
                    case ProductStatus::DISPLAY_DRAFT:
                        $this->addSuccess('下書き保存しました。');
                        break;
                        
                    default:
                        $this->addSuccess('削除しました。');
                        $cacheUtil->clearDoctrineCache();

                        return $this->redirectToRoute('mypage_blog_new');
                }

                if ($returnLink = $form->get('return_link')->getData()) {
                    try {
                        // $returnLinkはpathの形式で渡される. pathが存在するかをルータでチェックする.
                        $pattern = '/^'.preg_quote($request->getBasePath(), '/').'/';
                        $returnLink = preg_replace($pattern, '', $returnLink);
                        $result = $router->match($returnLink);
                        // パラメータのみ抽出
                        $params = array_filter($result, function ($key) {
                            return 0 !== \strpos($key, '_');
                        }, ARRAY_FILTER_USE_KEY);

                        // pathからurlを再構築してリダイレクト.
                        return $this->redirectToRoute($result['_route'], $params);
                    } catch (\Exception $e) {
                        // マッチしない場合はログ出力してスキップ.
                        log_warning('URLの形式が不正です。');
                    }
                }

                $cacheUtil->clearDoctrineCache();

                return $this->redirectToRoute('mypage_blog_edit', ['id' => $Product->getId()]);
            }
        }

        // Get Tags
        $TagsList = $this->tagRepository->getList();

        // ツリー表示のため、ルートからのカテゴリを取得
        $TopCategories = $this->categoryRepository->getList(null);
        $ChoicedCategoryIds = array_map(function ($Category) {
            return $Category->getId();
        }, $form->get('Category')->getData());
        
        return [
            'Product' => $Product,
            'Tags' => $Tags,
            'TagsList' => $TagsList,
            'form' => $form->createView(),
            'has_class' => $has_class,
            'id' => $id,
            'TopCategories' => $TopCategories,
            'ChoicedCategoryIds' => $ChoicedCategoryIds,
            'free_area' => $freeArea,
            'description_detail' => $descriptionDetail,
        ];
    }
    
    /**
     * 売上履歴一覧.
     *
     * @Route("/mypage/transfer_history", name="mypage_transfer_history", methods={"GET"})
     * @Template("Mypage/transfer_history.twig")
     */
    public function transferHistory(Request $request, $page_no = null, PaginatorInterface $paginator)
    {
        $transferHistoryRepository = $this->getDoctrine()->getRepository(TransferHistory::class);
        $Customer = $this->getUser();

        if ( $request->query->has('transfer_date') ) {
            $date = $request->query->get('transfer_date') . '-01' ;
            $startDate = new \DateTime(date('Y-m-01 00:00:00', strtotime($date)));
            $endDate = new \DateTime(date('Y-m-t 23:59:59', strtotime($date)));
        } else {
            $date = date('Y-m-01');
            $startDate = new \DateTime(date('Y-m-01 00:00:00'));
            $endDate = new \DateTime(date('Y-m-t 23:59:59'));
        }

        $orderItems = [];
        $transferHistoryRepository->aggregateMoneyByPeriod( $Customer, $startDate, $endDate, $orderItems );

        // $balance = 0;
        // if ( $transferHistory )
        //     $balance = $transferHistory->getBalance();
        
        $pagination = $paginator->paginate(
            $orderItems,
            $request->get('pageno', 1),
            20
        );

        return [
            'pagination' => $pagination,
            // 'balance' => $balance,
            'selectedMonth' => substr($date, 0, 7),
        ];
    }


    /**
     * 購入履歴詳細を表示する.
     *
     * @Route("/mypage/history/{order_no}", name="mypage_history", methods={"GET"})
     * @Template("Mypage/history.twig")
     */
    public function history(Request $request, $order_no)
    {
        $this->entityManager->getFilters()
            ->enable('incomplete_order_status_hidden');
        $Order = $this->orderRepository->findOneBy(
            [
                'order_no' => $order_no,
                'Customer' => $this->getUser(),
            ]
        );

        $event = new EventArgs(
            [
                'Order' => $Order,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_MYPAGE_HISTORY_INITIALIZE, $event);

        /** @var Order $Order */
        $Order = $event->getArgument('Order');

        if (!$Order) {
            throw new NotFoundHttpException();
        }

        $stockOrder = true;
        foreach ($Order->getOrderItems() as $orderItem) {
            if ($orderItem->isProduct() && $orderItem->getQuantity() < 0) {
                $stockOrder = false;
                break;
            }
        }

        return [
            'Order' => $Order,
            'stockOrder' => $stockOrder,
        ];
    }

    /**
     * 再購入を行う.
     *
     * @Route("/mypage/order/{order_no}", name="mypage_order", methods={"PUT"})
     */
    public function order(Request $request, $order_no)
    {
        $this->isTokenValid();

        log_info('再注文開始', [$order_no]);

        $Customer = $this->getUser();

        /* @var $Order \Eccube\Entity\Order */
        $Order = $this->orderRepository->findOneBy(
            [
                'order_no' => $order_no,
                'Customer' => $Customer,
            ]
        );

        $event = new EventArgs(
            [
                'Order' => $Order,
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_MYPAGE_ORDER_INITIALIZE, $event);

        if (!$Order) {
            log_info('対象の注文が見つかりません', [$order_no]);
            throw new NotFoundHttpException();
        }

        // エラーメッセージの配列
        $errorMessages = [];

        foreach ($Order->getOrderItems() as $OrderItem) {
            try {
                if ($OrderItem->getProduct() && $OrderItem->getProductClass()) {
                    $this->cartService->addProduct($OrderItem->getProductClass(), $OrderItem->getQuantity());

                    // 明細の正規化
                    $Carts = $this->cartService->getCarts();
                    foreach ($Carts as $Cart) {
                        $result = $this->purchaseFlow->validate($Cart, new PurchaseContext($Cart, $this->getUser()));
                        // 復旧不可のエラーが発生した場合は追加した明細を削除.
                        if ($result->hasError()) {
                            $this->cartService->removeProduct($OrderItem->getProductClass());
                            foreach ($result->getErrors() as $error) {
                                $errorMessages[] = $error->getMessage();
                            }
                        }
                        foreach ($result->getWarning() as $warning) {
                            $errorMessages[] = $warning->getMessage();
                        }
                    }

                    $this->cartService->save();
                }
            } catch (CartException $e) {
                log_info($e->getMessage(), [$order_no]);
                $this->addRequestError($e->getMessage());
            }
        }

        foreach ($errorMessages as $errorMessage) {
            $this->addRequestError($errorMessage);
        }

        $event = new EventArgs(
            [
                'Order' => $Order,
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_MYPAGE_ORDER_COMPLETE, $event);

        if ($event->getResponse() !== null) {
            return $event->getResponse();
        }

        log_info('再注文完了', [$order_no]);

        return $this->redirect($this->generateUrl('cart'));
    }

    /**
     * お気に入り商品を表示する.
     *
     * @Route("/mypage/favorite", name="mypage_favorite", methods={"GET"})
     * @Template("Mypage/favorite.twig")
     */
    public function favorite(Request $request, PaginatorInterface $paginator)
    {
        if (!$this->BaseInfo->isOptionFavoriteProduct()) {
            throw new NotFoundHttpException();
        }
        $Customer = $this->getUser();

        // paginator
        $qb = $this->customerFavoriteProductRepository->getQueryBuilderByCustomer($Customer);

        $event = new EventArgs(
            [
                'qb' => $qb,
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_MYPAGE_FAVORITE_SEARCH, $event);

        $pagination = $paginator->paginate(
            $qb,
            $request->get('pageno', 1),
            $this->eccubeConfig['eccube_search_pmax'],
            ['wrap-queries' => true]
        );

        return [
            'pagination' => $pagination,
        ];
    }

    /**
     * お気に入り商品を削除する.
     *
     * @Route("/mypage/favorite/{id}/delete", name="mypage_favorite_delete", methods={"DELETE"}, requirements={"id" = "\d+"})
     */
    public function delete(Request $request, Product $Product)
    {
        $this->isTokenValid();

        $Customer = $this->getUser();

        log_info('お気に入り商品削除開始', [$Customer->getId(), $Product->getId()]);

        $CustomerFavoriteProduct = $this->customerFavoriteProductRepository->findOneBy(['Customer' => $Customer, 'Product' => $Product]);

        if ($CustomerFavoriteProduct) {
            $this->customerFavoriteProductRepository->delete($CustomerFavoriteProduct);
        } else {
            throw new BadRequestHttpException();
        }

        $event = new EventArgs(
            [
                'Customer' => $Customer,
                'CustomerFavoriteProduct' => $CustomerFavoriteProduct,
            ], $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_MYPAGE_DELETE_COMPLETE, $event);

        log_info('お気に入り商品削除完了', [$Customer->getId(), $CustomerFavoriteProduct->getId()]);

        return $this->redirect($this->generateUrl('mypage_favorite'));
    }

    /**
     * @Route("/mypage/blog/image/add", name="mypage_blog_image_add", methods={"POST"})
     */
    public function addImage(Request $request)
    {
        if (!$request->isXmlHttpRequest() && $this->isTokenValid()) {
            throw new BadRequestHttpException();
        }

        $images = $request->files->get('admin_product');

        $allowExtensions = ['gif', 'jpg', 'jpeg', 'png'];
        $files = [];
        if (count($images) > 0) {
            foreach ($images as $img) {
                foreach ($img as $image) {
                    //ファイルフォーマット検証
                    $mimeType = $image->getMimeType();
                    if (0 !== strpos($mimeType, 'image')) {
                        throw new UnsupportedMediaTypeHttpException();
                    }

                    // 拡張子
                    $extension = $image->getClientOriginalExtension();
                    if (!in_array(strtolower($extension), $allowExtensions)) {
                        throw new UnsupportedMediaTypeHttpException();
                    }

                    $filename = date('mdHis').uniqid('_').'.'.$extension;
                    $image->move($this->eccubeConfig['eccube_temp_image_dir'], $filename);
                    $files[] = $filename;
                }
            }
        }

        return $this->json(['files' => $files], 200);
    }

    /**
     * ProductCategory作成
     *
     * @param \Eccube\Entity\Product $Product
     * @param \Eccube\Entity\Category $Category
     * @param integer $count
     *
     * @return \Eccube\Entity\ProductCategory
     */
    private function createProductCategory($Product, $Category, $count)
    {
        $ProductCategory = new ProductCategory();
        $ProductCategory->setProduct($Product);
        $ProductCategory->setProductId($Product->getId());
        $ProductCategory->setCategory($Category);
        $ProductCategory->setCategoryId($Category->getId());

        return $ProductCategory;
    }

    /**
     * @Route("/mypage/blog/upload", name="mypage_blog_upload", methods={"POST"})
     */
    public function uploadBlogImage(Request $request)
    {
        try {
            // File Route.
            $fileRoute = $this->eccubeConfig['eccube_save_image_dir'];

            reset ($_FILES);
            $temp = current($_FILES);

            if (is_uploaded_file($temp["tmp_name"])){

                if (preg_match("/([^wsd\-_~,;:[]().])|([.]{2,})/", $temp["name"])) {
                    throw new UnsupportedMediaTypeHttpException();
                }

                $extension = pathinfo($temp["name"], PATHINFO_EXTENSION);
                $fileName  = date('mdHis').uniqid('_').'.'.$extension;
                $fullNamePath = $fileRoute .'/'. $fileName;
                
                if (!in_array(strtolower($extension), array('gif', 'jpg', 'jpeg', 'png'))) {
                    throw new UnsupportedMediaTypeHttpException();
                }
                move_uploaded_file($temp["tmp_name"], $fullNamePath);
                
                return $this->json([
                    'uploaded' => 1,
                    'filename' => $fileName,
                    'location' => '/html/upload/save_image/' . $fileName,
                ]);
            } else {
                throw new \Exception("ファイルアップロードに失敗しました");
            }
        } catch (\Exception $exception) {
            return $this->json([
                'uploaded' => 0,
                'error' => array(
                    'message' => $exception->getMessage(),
                )
            ]);
        }
    }

    /**
     * 画像アップロード時にリクエストされるメソッド.
     *
     * @see https://pqina.nl/filepond/docs/api/server/#process
     * @Route("/mypage/blog/image/process", name="mypage_image_process", methods={"POST"})
     */
    public function imageProcess(Request $request)
    {
        if (!$request->isXmlHttpRequest() && $this->isTokenValid()) {
            throw new BadRequestHttpException();
        }

        $images = $request->files->get('admin_product');

        $allowExtensions = ['gif', 'jpg', 'jpeg', 'png'];
        $files = [];
        if (count($images) > 0) {
            foreach ($images as $img) {
                foreach ($img as $image) {
                    // ファイルフォーマット検証
                    $mimeType = $image->getMimeType();
                    if (0 !== strpos($mimeType, 'image')) {
                        throw new UnsupportedMediaTypeHttpException();
                    }

                    // 拡張子
                    $extension = $image->getClientOriginalExtension();
                    if (!in_array(strtolower($extension), $allowExtensions)) {
                        throw new UnsupportedMediaTypeHttpException();
                    }

                    $filename = date('mdHis').uniqid('_').'.'.$extension;
                    $image->move($this->eccubeConfig['eccube_temp_image_dir'], $filename);
                    $files[] = $filename;
                }
            }
        }

        return new Response(array_shift($files));
    }

    /**
     * アップロード画像を取得する際にコールされるメソッド.
     *
     * @see https://pqina.nl/filepond/docs/api/server/#load
     * @Route("/mypage/blog/image/load", name="mypage_image_load", methods={"GET"})
     */
    public function imageLoad(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $dirs = [
            $this->eccubeConfig['eccube_save_image_dir'],
            $this->eccubeConfig['eccube_temp_image_dir'],
        ];

        foreach ($dirs as $dir) {
            if (strpos($request->query->get('source'), '..') !== false) {
                throw new NotFoundHttpException();
            }
            $image = \realpath($dir.'/'.$request->query->get('source'));
            $dir = \realpath($dir);

            if (\is_file($image) && \str_starts_with($image, $dir)) {
                $file = new \SplFileObject($image);

                return $this->file($file, $file->getBasename());
            }
        }

        throw new NotFoundHttpException();
    }

    /**
     * アップロード画像をすぐ削除する際にコールされるメソッド.
     *
     * @see https://pqina.nl/filepond/docs/api/server/#revert
     * @Route("/mypage/blog/image/revert", name="mypage_image_revert", methods={"DELETE"})
     */
    public function imageRevert(Request $request)
    {
        if (!$request->isXmlHttpRequest() && $this->isTokenValid()) {
            throw new BadRequestHttpException();
        }

        $tempFile = $this->eccubeConfig['eccube_temp_image_dir'].'/'.$request->getContent();
        if (is_file($tempFile) && stripos(realpath($tempFile), $this->eccubeConfig['eccube_temp_image_dir']) === 0) {
            $fs = new Filesystem();
            $fs->remove($tempFile);

            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        throw new NotFoundHttpException();
    }
}
