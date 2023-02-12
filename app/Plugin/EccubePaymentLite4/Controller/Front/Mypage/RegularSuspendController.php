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

class RegularSuspendController extends AbstractController
{
    /**
     * @var RegularStatusRepository
     */
    private $regularStatusRepository;
    /**
     * @var IsMypageRegularSettingService
     */
    private $isMypageRegularSettingService;
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
     * 定期受注休止確認画面を表示する。
     *
     * @Route(
     *     "/mypage/eccube_payment_lite/regular/{id}/suspend",
     *     name="eccube_payment_lite4_mypage_regular_suspend",
     *     requirements={"id" = "\d+"}
     * )
     * @Template("@EccubePaymentLite4/default/Mypage/regular_suspend.twig")
     */
    public function suspend(RegularOrder $RegularOrder, Request $request)
    {
        if (!$this->isActiveRegularService->isActive()) {
            return $this->redirectToRoute('mypage');
        }
        if ($RegularOrder->getRegularStatus()->getId() !== RegularStatus::CONTINUE) {
            return $this->redirectToRoute('eccube_payment_lite4_mypage_regular_list');
        }
        if (!$this->isMypageRegularSettingService->handle(MyPageRegularSetting::SUSPEND_AND_RESUME)) {
            return $this->redirectToRoute('eccube_payment_lite4_mypage_regular_list');
        }
        $builder = $this->formFactory->createBuilder();
        $form = $builder->getForm();
        $form->handleRequest($request);
        // 休止回数のチェック
        if (!$this->isPossibleToSuspend($RegularOrder->getRegularOrderCount())) {
            $form->addError(new FormError(''));
            $this->addWarning('定期商品の休止が可能な購入回数に達していないため、休止できません。');
        }
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var RegularStatus $RegularStatus */
            $RegularStatus = $this->regularStatusRepository->find(RegularStatus::SUSPEND);
            /** @var RegularShipping $RegularShipping */
            $RegularShipping = $RegularOrder->getRegularShippings()->first();
            $RegularShipping->setNextDeliveryDate(null);
            $RegularOrder->setRegularStatus($RegularStatus);
            $RegularOrder->setRegularStopDate(new \DateTime());
            $this->entityManager->persist($RegularOrder);
            $this->entityManager->flush();
            $this->addWarning('定期商品のご注文を休止しました。');

            return $this->redirectToRoute('eccube_payment_lite4_mypage_regular_complete', [
                'id' => $RegularOrder->getId(),
            ]);
        }

        return [
            'form' => $form->createView(),
            'RegularOrder' => $RegularOrder,
        ];
    }

    private function isPossibleToSuspend($regularOrderCount): bool
    {
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        $regularStoppableCount = $Config->getRegularStoppableCount();
        if ($regularOrderCount < $regularStoppableCount) {
            return false;
        }

        return true;
    }
}
