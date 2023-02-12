<?php

namespace Plugin\EccubePaymentLite4\Controller\Front\Mypage;

use Eccube\Controller\AbstractController;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Repository\RegularOrderRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class RegularDetailController extends AbstractController
{
    /**
     * @var RegularOrderRepository
     */
    private $regularOrderRepository;

    public function __construct(
        RegularOrderRepository $regularOrderRepository
    ) {
        $this->regularOrderRepository = $regularOrderRepository;
    }

    /**
     * 定期購入詳細を表示する.
     *
     * @Route(
     *     "/mypage/eccube_payment_lite/regular/{id}/detail",
     *     name="eccube_payment_lite4_mypage_regular_detail",
     *     requirements={"id" = "\d+"}
     * )
     * @Template("@EccubePaymentLite4/default/Mypage/regular_detail.twig")
     */
    public function detail(Request $request, RegularOrder $RegularOrder)
    {
        /** @var RegularOrder $RegularOrder */
        $RegularOrder = $this->regularOrderRepository->find($RegularOrder->getId());

        if (!$RegularOrder) {
            throw new NotFoundHttpException();
        }

        return [
            'RegularOrder' => $RegularOrder,
        ];
    }
}
