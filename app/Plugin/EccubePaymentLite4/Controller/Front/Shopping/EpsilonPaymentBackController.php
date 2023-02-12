<?php

namespace Plugin\EccubePaymentLite4\Controller\Front\Shopping;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Service\PointHelper;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestGetSales2Service;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class EpsilonPaymentBackController extends AbstractController
{
    /**
     * @var RequestGetSales2Service
     */
    private $requestGetSales2Service;
    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var PurchaseFlow
     */
    private $purchaseFlow;
    /**
     * @var PointHelper
     */
    protected $pointHelper;

    public function __construct(
        RequestGetSales2Service $requestGetSales2Service,
        OrderStatusRepository $orderStatusRepository,
        OrderRepository $orderRepository,
        PurchaseFlow $shoppingPurchaseFlow,
        PointHelper $pointHelper
    ) {
        $this->requestGetSales2Service = $requestGetSales2Service;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->orderRepository = $orderRepository;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->pointHelper = $pointHelper;
    }

    /**
     * @Route(
     *     "/shopping/epsilon_payment/back",
     *     name="eccube_payment_lite4_payment_back"
     * )
     *
     * @return RedirectResponse
     */
    public function back(Request $request)
    {
        $transCode = '';
        $orderNumber = '';
        if ($request->query->get('trans_code')) {
            $transCode = $request->query->get('trans_code');
        }
        if ($request->query->get('order_number')) {
            $orderNumber = $request->query->get('order_number');
        }
        $results = $this->requestGetSales2Service->handle($transCode, $orderNumber);
        // マイページよりクレジットカードの登録を行った場合は、カード編集画面にリダイレクトさせる
        if ($results['route'] === 'eccube_payment_lite4_mypage_credit_card_edit' || $results['route'] === 'eccube_payment_lite4_mypage_credit_card_new') {
            return $this->redirectToRoute('eccube_payment_lite4_mypage_credit_card_index');
        }
        /** @var OrderStatus $pendingOrderStatus */
        $pendingOrderStatus = $this->orderStatusRepository->find(OrderStatus::PENDING);

        // 決済会社から受注番号を受け取る
        $orderId = $this->getOrderId($orderNumber);

        /** @var Order $Order */
        $Order = $this->orderRepository->findOneBy([
            'id' => $orderId,
            'OrderStatus' => $pendingOrderStatus,
        ]);

        if (!$Order) {
            throw new NotFoundHttpException();
        }

        if ($this->getUser() != $Order->getCustomer()) {
            throw new NotFoundHttpException();
        }

        // 受注ステータスを購入処理中へ変更
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PROCESSING);
        $Order->setOrderStatus($OrderStatus);

        // purchaseFlow::rollbackを呼び出し, 購入処理をロールバックする.
        $this->purchaseFlow->rollback($Order, new PurchaseContext());

        // Calculator point for customer
        if ($Order->getUsePoint() > 0) {
            // ユーザの保有ポイントを減算
            $this->pointHelper->prepare($Order, $Order->getUsePoint());
        }
        $this->entityManager->flush();

        return $this->redirectToRoute('shopping');
    }

    /**
     * レスポンスパラメータorder_numberから受注番号を取得
     *
     * @var order_number 受注番号xリクエスト日時 (ex. 12345x20190301)
     *
     * @return integer
     */
    private function getOrderId($order_number)
    {
        if (empty($order_number)) {
            return null;
        } elseif (is_numeric($order_number)) {
            return $order_number;
        } else {
            \preg_match('/(\d+)x(\d+)/', $order_number, $matchs);

            return $matchs[1];
        }
    }
}
