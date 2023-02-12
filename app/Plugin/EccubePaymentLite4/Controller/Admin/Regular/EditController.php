<?php

namespace Plugin\EccubePaymentLite4\Controller\Admin\Regular;

use Doctrine\Common\Collections\ArrayCollection;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\SaleType;
use Eccube\Entity\Order;
use Eccube\Entity\Product;
use Eccube\Form\Type\AddCartType;
use Eccube\Form\Type\Admin\SearchProductType;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\DeliveryRepository;
use Eccube\Repository\Master\SaleTypeRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Knp\Component\Pager\Pagination\SlidingPagination;
use Knp\Component\Pager\PaginatorInterface;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Plugin\EccubePaymentLite4\Form\Type\Admin\RegularOrderType;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Repository\SearchProductRepository;
use Plugin\EccubePaymentLite4\Repository\RegularOrderRepository;
use Plugin\EccubePaymentLite4\Service\CalculateNextDeliveryDateService;
use Plugin\EccubePaymentLite4\Service\IsActiveRegularService;
use Plugin\EccubePaymentLite4\Service\RegularCreditService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class EditController extends AbstractController
{
    /**
     * @var RegularOrderRepository
     */
    private $regularOrderRepository;
    /**
     * @var RegularCreditService
     */
    private $regularCreditService;
    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var DeliveryRepository
     */
    private $deliveryRepository;
    /**
     * @var PurchaseFlow
     */
    private $purchaseFlow;
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var CalculateNextDeliveryDateService
     */
    private $calculateNextDeliveryDateService;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var IsActiveRegularService
     */
    private $isActiveRegularService;

    /**
     * @var SearchProductRepository
     */
    private $searchProductRepository;

    /**
     * @var SaleTypeRepository
     */
    private $saleTypeRepository;

    public function __construct(
        RegularOrderRepository $regularOrderRepository,
        RegularCreditService $regularCreditService,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        SerializerInterface $serializer,
        DeliveryRepository $deliveryRepository,
        PurchaseFlow $orderPurchaseFlow,
        OrderRepository $orderRepository,
        CalculateNextDeliveryDateService $calculateNextDeliveryDateService,
        ConfigRepository $configRepository,
        IsActiveRegularService $isActiveRegularService,
        SearchProductRepository $searchProductRepository,
        SaleTypeRepository $saleTypeRepository
    ) {
        $this->regularOrderRepository = $regularOrderRepository;
        $this->regularCreditService = $regularCreditService;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->serializer = $serializer;
        $this->deliveryRepository = $deliveryRepository;
        $this->purchaseFlow = $orderPurchaseFlow;
        $this->orderRepository = $orderRepository;
        $this->calculateNextDeliveryDateService = $calculateNextDeliveryDateService;
        $this->configRepository = $configRepository;
        $this->isActiveRegularService = $isActiveRegularService;
        $this->searchProductRepository = $searchProductRepository;
        $this->saleTypeRepository = $saleTypeRepository;
    }

    /**
     * @Route(
     *     "/%eccube_admin_route%/eccube_payment_lite/regular/order/{id}/edit",
     *     name="eccube_payment_lite4_admin_regular_order_edit",
     *     requirements={"id" = "\d+"}
     * )
     * @Template("@EccubePaymentLite4/admin/Regular/Order/edit.twig")
     */
    public function edit(Request $request, $id = null)
    {
        if (!$this->isActiveRegularService->isActive()) {
            throw new NotFoundHttpException();
        }
        /** @var RegularOrder $TargetRegularOrder */
        $TargetRegularOrder = $this->regularOrderRepository->find($id);
        if ($TargetRegularOrder === null) {
            throw new NotFoundHttpException();
        }

        // 編集前の受注情報を保持
        $OriginRegularOrder = clone $TargetRegularOrder;
        $OriginItems = new ArrayCollection();
        foreach ($TargetRegularOrder->getRegularOrderItems() as $Item) {
            $OriginItems->add($Item);
        }

        $form = $this->createForm(RegularOrderType::class, $TargetRegularOrder);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $OriginOrder = $this->regularCreditService->createOrderData($OriginRegularOrder);
            $TargetOrder = $this->regularCreditService->createOrderData($TargetRegularOrder);

            // apply purchase
            $purchaseContext = new PurchaseContext($OriginOrder, $OriginOrder->getCustomer());
            $flowResult = $this->purchaseFlow->validate($TargetOrder, $purchaseContext);

            $this->calculate($TargetRegularOrder, $TargetOrder);

            // 登録ボタン押下
            switch ($request->get('mode')) {
                case 'register':
                    if (!$flowResult->hasError() && $form->isValid()) {
                        $this->entityManager->persist($TargetRegularOrder);
                        $this->entityManager->flush();

                        foreach ($OriginItems as $Item) {
                            if (!$TargetRegularOrder->getRegularOrderItems()->contains($Item)) {
                                $this->entityManager->remove($Item);
                            }
                        }

                        // 休止、解約にする場合は次回お届け予定日を空にする
                        if ($OriginRegularOrder->getRegularStatus()->getId() === RegularStatus::CONTINUE &&
                             ($TargetRegularOrder->getRegularStatus()->getId() === RegularStatus::SUSPEND ||
                             $TargetRegularOrder->getRegularStatus()->getId() === RegularStatus::CANCELLATION)
                        ) {
                            $TargetRegularOrder->getRegularShippings()->first()->setNextDeliveryDate(null);
                            $this->entityManager->persist($TargetRegularOrder);
                        }

                        // 再開時は次回お届け予定日を再計算して保存する
                        /** @var Config $Config */
                        $Config = $this->configRepository->find(1);
                        if ($OriginRegularOrder->getRegularStatus()->getId() === RegularStatus::CANCELLATION &&
                           $TargetRegularOrder->getRegularStatus()->getId() === RegularStatus::CONTINUE
                        ) {
                            $TargetRegularOrder->getRegularShippings()->first()->setNextDeliveryDate(
                                $this->calculateNextDeliveryDateService->calc($TargetRegularOrder->getRegularCycle(), $Config->getFirstDeliveryDays())
                            );
                        }

                        // 休止にする場合は、定期休止日を設定する
                        if ($TargetRegularOrder->getRegularStatus()->getId() === RegularStatus::SUSPEND) {
                            $TargetRegularOrder->setRegularStopDate(new \DateTime());
                        }
                        $this->entityManager->flush();

                        $this->addSuccess('admin.common.save_complete', 'admin');

                        return $this->redirectToRoute('eccube_payment_lite4_admin_regular_order_edit', ['id' => $TargetRegularOrder->getId()]);
                    }
                    break;
                default:
                    break;
            }
        }

        // 商品検索フォーム
        $searchProductModalForm = $this->createForm(SearchProductType::class);

        // 配送業者のお届け時間
        $times = [];
        $deliveries = $this->deliveryRepository->findAll();
        foreach ($deliveries as $Delivery) {
            $deliveryTimes = $Delivery->getDeliveryTimes();
            foreach ($deliveryTimes as $DeliveryTime) {
                $times[$Delivery->getId()][$DeliveryTime->getId()] = $DeliveryTime->getDeliveryTime();
            }
        }

        $OrderHistories = $this->orderRepository->findby([
            'RegularOrder' => $TargetRegularOrder,
        ]);

        return [
            'form' => $form->createView(),
            'searchProductModalForm' => $searchProductModalForm->createView(),
            'RegularOrder' => $TargetRegularOrder,
            'id' => $id,
            'shippingDeliveryTimes' => $this->serializer->serialize($times, 'json'),
            'OrderHistories' => $OrderHistories,
        ];
    }

    /**
     * @Route(
     *     "/%eccube_admin_route%/eccube_payment_lite/regular/search_product",
     *     name="eccube_payment_lite4_admin_regular_order_search_product"
     * )
     * @Route(
     *     "/%eccube_admin_route%/epsilon_regular_order/regular/search_product/page/{page_no}",
     *     requirements={"page_no" = "\d+"},
     *     name="eccube_payment_lite4_admin_regular_order_search_product_page"
     * )
     * @Template("@EccubePaymentLite4/admin/Order/regular_search_product.twig")
     */
    public function searchProduct(Request $request, $page_no = null, PaginatorInterface $paginator)
    {
        if ($request->isXmlHttpRequest() && $this->isTokenValid()) {
            log_debug('search regular product start.');
            $page_count = $this->eccubeConfig['eccube_default_page_count'];
            if ('POST' === $request->getMethod()) {
                $page_no = 1;

                $searchData = [
                    'id' => $request->get('id'),
                ];

                if ($categoryId = $request->get('category_id')) {
                    $Category = $this->categoryRepository->find($categoryId);
                    $searchData['category_id'] = $Category;
                }

                $this->session->set('eccube.admin.order.regular_product.search', $searchData);
                $this->session->set('eccube.admin.order.regular_product.search.page_no', $page_no);
            } else {
                $searchData = (array) $this->session->get('eccube.admin.order.regular_product.search');
                if (is_null($page_no)) {
                    $page_no = intval($this->session->get('eccube.admin.order.regular_product.search.page_no'));
                } else {
                    $this->session->set('eccube.admin.order.regular_product.search.page_no', $page_no);
                }
            }

            /** @var SaleType $SaleType */
            $SaleType = $this->saleTypeRepository->findOneBy([
                'name' => '定期商品',
            ]);

            $qb = $this->searchProductRepository->getQueryBuilderBySearchDataProductForAdmin($searchData, $SaleType);

            /** @var SlidingPagination $pagination */
            $pagination = $paginator->paginate(
                $qb,
                $page_no,
                $page_count,
                ['wrap-queries' => true]
            );

            /** @var $Products \Eccube\Entity\Product[] */
            $Products = $pagination->getItems();

            if (empty($Products)) {
                log_debug('search product not found.');
            }

            $forms = [];
            foreach ($Products as $Product) {
                $builder = $this->formFactory->createNamedBuilder('', AddCartType::class, null, [
                    'product' => $this->productRepository->findWithSortedClassCategories($Product->getId()),
                ]);
                $addCartForm = $builder->getForm();
                $forms[$Product->getId()] = $addCartForm->createView();
            }

            return [
                'forms' => $forms,
                'Products' => $Products,
                'pagination' => $pagination,
            ];
        }
    }

    /**
     * Calculate RegularOrder
     *
     * @param RegularOrder $RegularOrder
     * @param Order $Order
     */
    public function calculate($RegularOrder, $Order)
    {
        $RegularOrder
            ->setSubtotal($Order->getSubtotal())
            ->setDiscount($Order->getDiscount())
            ->setDeliveryFeeTotal($Order->getDeliveryFeeTotal())
            ->setCharge($Order->getCharge())
            ->setTax($Order->getTax())
            ->setTotal($Order->getTotal())
            ->setPaymentTotal($Order->getPaymentTotal());
    }
}
