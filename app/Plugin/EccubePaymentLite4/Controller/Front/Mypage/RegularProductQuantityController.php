<?php

namespace Plugin\EccubePaymentLite4\Controller\Front\Mypage;

use Eccube\Controller\AbstractController;
use Plugin\EccubePaymentLite4\Entity\MyPageRegularSetting;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularOrderItem;
use Plugin\EccubePaymentLite4\Repository\RegularOrderRepository;
use Plugin\EccubePaymentLite4\Repository\RegularOrderItemRepository;
use Plugin\EccubePaymentLite4\Form\Type\Front\RegularProductQuantityType;
use Plugin\EccubePaymentLite4\Service\IsActiveRegularService;
use Plugin\EccubePaymentLite4\Service\IsMypageRegularSettingService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Plugin\EccubePaymentLite4\Service\ModifyRegularOrderService;

class RegularProductQuantityController extends AbstractController
{
    /**
     * @var RegularOrderRepository
     */
    private $regularOrderRepository;
    /**
     * @var RegularOrderItemRepository
     */
    private $regularOrderItemRepository;
    /**
     * @var IsMypageRegularSettingService
     */
    private $isMypageRegularSettingService;
    /**
     * @var IsActiveRegularService
     */
    private $isActiveRegularService;
    /**
     * @var ModifyRegularOrderService
     */
    private $modifyRegularOrderService;

    public function __construct(
        RegularOrderRepository $regularOrderRepository,
        RegularOrderItemRepository $regularOrderItemRepository,
        IsMypageRegularSettingService $isMypageRegularSettingService,
        IsActiveRegularService $isActiveRegularService,
        ModifyRegularOrderService $modifyRegularOrderService
    ) {
        $this->regularOrderRepository = $regularOrderRepository;
        $this->regularOrderItemRepository = $regularOrderItemRepository;
        $this->isMypageRegularSettingService = $isMypageRegularSettingService;
        $this->isActiveRegularService = $isActiveRegularService;
        $this->modifyRegularOrderService = $modifyRegularOrderService;
    }

    /**
     * 定期受注休止確認画面を表示する。
     *
     * @Route(
     *     "/mypage/eccube_payment_lite/regular/{id}/product_quantity",
     *     name="eccube_payment_lite4_mypage_regular_product_quantity",
     *     requirements={"id" = "\d+"}
     * )
     * @Template("@EccubePaymentLite4/default/Mypage/regular_product_quantity.twig")
     */
    public function index(RegularOrder $RegularOrder, Request $request)
    {
        if (!$this->isActiveRegularService->isActive()) {
            return $this->redirectToRoute('mypage');
        }
        if (!$this->isMypageRegularSettingService->handle(MyPageRegularSetting::NUMBER_OR_ITEMS)) {
            return $this->redirectToRoute('eccube_payment_lite4_mypage_regular_list');
        }
        /** @var RegularOrder $RegularOrder */
        $RegularOrder = $this->regularOrderRepository->find($RegularOrder->getId());
        $form = $this->createForm(RegularProductQuantityType::class, $RegularOrder);
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                /** @var RegularOrder $RegularOrder */
                $RegularOrder = $form->getData();
                $this->modifyRegularOrderService->reCalculateAll($RegularOrder);
                $this->entityManager->persist($RegularOrder);
                $this->entityManager->flush();
                $this->addWarning('定期商品のお届け商品数を変更しました。');

                return $this->redirectToRoute('eccube_payment_lite4_mypage_regular_complete', [
                    'id' => $RegularOrder->getId(),
                ]);
            }
        }

        return [
            'form' => $form->createView(),
            'RegularOrder' => $RegularOrder,
        ];
    }
}
