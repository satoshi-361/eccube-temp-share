<?php

namespace Plugin\EccubePaymentLite4\Controller\Front\Mypage;

use Eccube\Controller\AbstractController;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularShipping;
use Plugin\EccubePaymentLite4\Form\Type\Front\RegularShippingType;
use Plugin\EccubePaymentLite4\Service\IsActiveRegularService;
use Plugin\EccubePaymentLite4\Service\IsMypageRegularSettingService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class RegularShippingController extends AbstractController
{
    /**
     * @var IsMypageRegularSettingService
     */
    private $isMypageRegularSettingService;
    /**
     * @var IsActiveRegularService
     */
    private $isActiveRegularService;

    public function __construct(
        IsMypageRegularSettingService $isMypageRegularSettingService,
        IsActiveRegularService $isActiveRegularService
    ) {
        $this->isMypageRegularSettingService = $isMypageRegularSettingService;
        $this->isActiveRegularService = $isActiveRegularService;
    }

    /**
     * お届け先変更画面.
     *
     * @Route(
     *     "/mypage/eccube_payment_lite/regular/{id}/shipping",
     *     name="eccube_payment_lite4_mypage_regular_shipping",
     *     requirements={"id" = "\d+"}
     * )
     * @Template("@EccubePaymentLite4/default/Mypage/regular_shipping.twig")
     */
    public function index(Request $request, RegularOrder $RegularOrder)
    {
        if (!$this->isActiveRegularService->isActive()) {
            return $this->redirectToRoute('mypage');
        }
        /** @var RegularShipping $RegularShipping */
        $RegularShipping = $RegularOrder->getRegularShippings()->first();
        /** @var Config $Config */
        $form = $this->createForm(RegularShippingType::class, $RegularShipping);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($RegularShipping);
            $this->entityManager->flush();
            $this->addWarning('定期商品のお届け先を変更しました。');
            $this->addWarning('通常商品のお届け先は変更されませんのでご注意ください。');

            return $this->redirectToRoute('eccube_payment_lite4_mypage_regular_complete', [
                'id' => $RegularOrder->getId(),
            ]);
        }

        return [
            'RegularOrder' => $RegularOrder,
            'form' => $form->createView(),
        ];
    }
}
