<?php

namespace Plugin\EccubePaymentLite4\Controller\Front\Mypage;

use Eccube\Controller\AbstractController;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Service\IsActiveRegularService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class RegularCompleteController extends AbstractController
{
    /**
     * @var IsActiveRegularService
     */
    private $isActiveRegularService;

    public function __construct(
        IsActiveRegularService $isActiveRegularService
    ) {
        $this->isActiveRegularService = $isActiveRegularService;
    }

    /**
     * /**
     * 完了画面を表示する。
     *
     * @Route(
     *     "/mypage/eccube_payment_lite/regular/{id}/complete",
     *     name="eccube_payment_lite4_mypage_regular_complete",
     *     requirements={"id" = "\d+"}
     * )
     * @Template("@EccubePaymentLite4/default/Mypage/regular_complete.twig")
     *
     * @return array|RedirectResponse
     */
    public function complete(RegularOrder $RegularOrder)
    {
        if (!$this->isActiveRegularService->isActive()) {
            return $this->redirectToRoute('mypage');
        }

        return [
            'RegularOrder' => $RegularOrder,
        ];
    }
}
