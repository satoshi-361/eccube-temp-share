<?php

namespace Plugin\EccubePaymentLite4\Controller\Front\Mypage;

use Eccube\Controller\AbstractController;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\MyPageRegularSetting;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularShipping;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Repository\RegularStatusRepository;
use Plugin\EccubePaymentLite4\Service\IsActiveRegularService;
use Plugin\EccubePaymentLite4\Service\IsMypageRegularSettingService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class RegularCancelController extends AbstractController
{
    /**
     * @var RegularStatusRepository
     */
    private $regularStatusRepository;
    /**
     * @var IsMypageRegularSettingService
     */
    private $isMypageRegularSettingService;
    /**
     * @var IsActiveRegularService
     */
    private $isActiveRegularService;
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(
        RegularStatusRepository $regularStatusRepository,
        IsMypageRegularSettingService $isMypageRegularSettingService,
        IsActiveRegularService $isActiveRegularService,
        ConfigRepository $configRepository
    ) {
        $this->regularStatusRepository = $regularStatusRepository;
        $this->isMypageRegularSettingService = $isMypageRegularSettingService;
        $this->isActiveRegularService = $isActiveRegularService;
        $this->configRepository = $configRepository;
    }

    /**
     * 定期受注解約確認画面を表示する。
     *
     * @Route(
     *     "/mypage/eccube_payment_lite/regular/{id}/cancel",
     *     name="eccube_payment_lite4_mypage_regular_cancel",
     *     requirements={"id" = "\d+"})
     * @Template("@EccubePaymentLite4/default/Mypage/regular_cancel.twig")
     */
    public function cancel(Request $request, RegularOrder $RegularOrder)
    {
        if (!$this->isActiveRegularService->isActive()) {
            return $this->redirectToRoute('mypage');
        }
        if ($RegularOrder->getRegularStatus()->getId() !== RegularStatus::CONTINUE && $RegularOrder->getRegularStatus()->getId() !== RegularStatus::SUSPEND) {
            return $this->redirectToRoute('eccube_payment_lite4_mypage_regular_list');
        }
        if (!$this->isMypageRegularSettingService->handle(MyPageRegularSetting::CANCELLATION)) {
            return $this->redirectToRoute('eccube_payment_lite4_mypage_regular_list');
        }
        $builder = $this->formFactory->createBuilder();
        $form = $builder->getForm();
        $form->handleRequest($request);
        // 解約可能な定期回数かチェック
        if (!$this->isPossibleToCancel($RegularOrder->getRegularOrderCount())) {
            $form->addError(new FormError(''));
            $this->addWarning('定期商品の解約が可能な購入回数に達していないため、解約できません。');
        }
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var RegularStatus $RegularStatus */
            $RegularStatus = $this->regularStatusRepository->find(RegularStatus::CANCELLATION);
            /** @var RegularShipping $RegularShipping */
            $RegularShipping = $RegularOrder->getRegularShippings()->first();
            $RegularShipping->setNextDeliveryDate(null);
            $RegularOrder->setRegularStatus($RegularStatus);
            $this->entityManager->persist($RegularOrder);
            $this->entityManager->flush();
            $this->addWarning('定期商品のご注文を解約しました。');

            return $this->redirectToRoute('eccube_payment_lite4_mypage_regular_complete', [
                'id' => $RegularOrder->getId(),
            ]);
        }

        return [
            'form' => $form->createView(),
            'RegularOrder' => $RegularOrder,
        ];
    }

    private function isPossibleToCancel($regularOrderCount): bool
    {
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        $regularCancelableCount = $Config->getRegularCancelableCount();
        if ($regularOrderCount < $regularCancelableCount) {
            return false;
        }

        return true;
    }
}
