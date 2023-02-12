<?php

namespace Plugin\EccubePaymentLite4\Controller\Front\Shopping;

use Eccube\Controller\AbstractController;
use Eccube\Form\Type\Shopping\OrderType;
use Eccube\Service\CartService;
use Eccube\Service\OrderHelper;
use Plugin\EccubePaymentLite4\Form\Type\Front\CreditCardForTokenPaymentType;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequestService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonUrlService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CreditCardForTokenPaymentController extends AbstractController
{
    /**
     * @var GmoEpsilonUrlService
     */
    private $gmoEpsilonUrlService;
    /**
     * @var CartService
     */
    private $cartService;
    /**
     * @var OrderHelper
     */
    private $orderHelper;
    /**
     * @var GmoEpsilonRequestService
     */
    private $gmoEpsilonRequestService;

    public function __construct(
        GmoEpsilonUrlService $gmoEpsilonUrlService,
        CartService $cartService,
        OrderHelper $orderHelper,
        GmoEpsilonRequestService $gmoEpsilonRequestService
    ) {
        $this->gmoEpsilonUrlService = $gmoEpsilonUrlService;
        $this->cartService = $cartService;
        $this->orderHelper = $orderHelper;
        $this->gmoEpsilonRequestService = $gmoEpsilonRequestService;
    }

    /**
     * @Route(
     *     "/shopping/eccube_payment_lite/credit_card",
     *     name="eccube_payment_lite4_credit_card_for_token_payment"
     * )
     * @Template("@EccubePaymentLite4/default/Shopping/credit_card_for_token_payment.twig")
     */
    public function index(Request $request)
    {
        // ログイン状態のチェック.
        if ($this->orderHelper->isLoginRequired()) {
            log_info('[注文確認] 未ログインもしくはRememberMeログインのため, ログイン画面に遷移します.');

            return $this->redirectToRoute('shopping_login');
        }

        // 受注の存在チェック
        $preOrderId = $this->cartService->getPreOrderId();
        $Order = $this->orderHelper->getPurchaseProcessingOrder($preOrderId);
        if (!$Order) {
            log_info('[注文確認] 購入処理中の受注が存在しません.', [$preOrderId]);

            return $this->redirectToRoute('shopping_error');
        }
        $form = $this->createForm(CreditCardForTokenPaymentType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $checkoutForm = $this->createForm(OrderType::class, $Order);

            return [
                'url_token_js' => $this->gmoEpsilonUrlService->getUrl('token'),
                'form' => $form->createView(),
                'checkoutForm' => $checkoutForm->createView(),
                'token' => $form->getData()['token'],
            ];
        }

        return [
            'url_token_js' => $this->gmoEpsilonUrlService->getUrl('token'),
            'form' => $form->createView(),
        ];
    }
}
