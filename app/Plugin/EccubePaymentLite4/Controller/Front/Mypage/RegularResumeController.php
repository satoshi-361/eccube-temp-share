<?php

namespace Plugin\EccubePaymentLite4\Controller\Front\Mypage;

use Eccube\Controller\AbstractController;
use Plugin\EccubePaymentLite4\Entity\MyPageRegularSetting;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularShipping;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Plugin\EccubePaymentLite4\Repository\RegularStatusRepository;
use Plugin\EccubePaymentLite4\Service\GetNextDeliveryDateWhenResumingService;
use Plugin\EccubePaymentLite4\Service\IsActiveRegularService;
use Plugin\EccubePaymentLite4\Service\IsMypageRegularSettingService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class RegularResumeController extends AbstractController
{
    /**
     * @var RegularStatusRepository
     */
    private $regularStatusRepository;
    /**
     * @var GetNextDeliveryDateWhenResumingService
     */
    private $getNextDeliveryDateWhenResumingService;
    /**
     * @var IsMypageRegularSettingService
     */
    private $isMypageRegularSettingService;
    /**
     * @var IsActiveRegularService
     */
    private $isActiveRegularService;

    public function __construct(
        RegularStatusRepository $regularStatusRepository,
        GetNextDeliveryDateWhenResumingService $getNextDeliveryDateWhenResumingService,
        IsMypageRegularSettingService $isMypageRegularSettingService,
        IsActiveRegularService $isActiveRegularService
    ) {
        $this->regularStatusRepository = $regularStatusRepository;
        $this->getNextDeliveryDateWhenResumingService = $getNextDeliveryDateWhenResumingService;
        $this->isMypageRegularSettingService = $isMypageRegularSettingService;
        $this->isActiveRegularService = $isActiveRegularService;
    }

    /**
     * @Route(
     *     "/mypage/eccube_payment_lite/regular/{id}/resume",
     *     name="eccube_payment_lite4_mypage_regular_resume",
     *     requirements={"id" = "\d+"}
     * )
     * @Template("@EccubePaymentLite4/default/Mypage/regular_resume.twig")
     */
    public function resume(Request $request, RegularOrder $RegularOrder)
    {
        if (!$this->isActiveRegularService->isActive()) {
            return $this->redirectToRoute('mypage');
        }
        if ($RegularOrder->getRegularStatus()->getId() !== RegularStatus::SUSPEND) {
            return $this->redirectToRoute('eccube_payment_lite4_mypage_regular_list');
        }
        if (!$this->isMypageRegularSettingService->handle(MyPageRegularSetting::SUSPEND_AND_RESUME)) {
            return $this->redirectToRoute('eccube_payment_lite4_mypage_regular_list');
        }
        $nextDeliveryDate = $this->getNextDeliveryDateWhenResumingService->get();
        $builder = $this->formFactory->createBuilder();
        $form = $builder->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var RegularStatus $RegularStatus */
            $RegularStatus = $this->regularStatusRepository->find(RegularStatus::CONTINUE);
            /** @var RegularShipping $RegularShipping */
            $RegularShipping = $RegularOrder->getRegularShippings()->first();
            $RegularShipping->setNextDeliveryDate($nextDeliveryDate);
            $RegularOrder->setRegularStatus($RegularStatus);
            $this->entityManager->persist($RegularOrder);
            $this->entityManager->flush();
            $this->addWarning('定期商品のご注文を再開しました。');

            return $this->redirectToRoute('eccube_payment_lite4_mypage_regular_complete', [
                'id' => $RegularOrder->getId(),
            ]);
        }

        return [
            'form' => $form->createView(),
            'RegularOrder' => $RegularOrder,
            'nextDeliveryDate' => $nextDeliveryDate,
        ];
    }
}
