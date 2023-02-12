<?php

namespace Plugin\EccubePaymentLite4\Controller\Front\Shopping;

use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Repository\MailHistoryRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Eccube\Service\OrderHelper;
use Eccube\Service\Payment\PaymentMethodInterface;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestGetSales2Service;
use Plugin\EccubePaymentLite4\Service\UpdateGmoEpsilonOrderService;
use Plugin\EccubePaymentLite4\Service\UpdateRegularOrderService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class EpsilonPaymentCompleteController extends AbstractController
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;
    /**
     * @var RequestGetSales2Service
     */
    private $requestGetSales2Service;
    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;
    /**
     * @var UpdateRegularOrderService
     */
    private $updateRegularOrderService;
    /**
     * @var UpdateGmoEpsilonOrderService
     */
    private $updateGmoEpsilonOrderService;
    /**
     * @var CartService
     */
    private $cartService;
    /**
     * @var MailService
     */
    private $mailService;
    /**
     * @var MailHistoryRepository
     */
    private $mailHistoryRepository;

    public function __construct(
        OrderRepository $orderRepository,
        OrderStatusRepository $orderStatusRepository,
        RequestGetSales2Service $requestGetSales2Service,
        CsrfTokenManagerInterface $csrfTokenManager,
        UpdateRegularOrderService $updateRegularOrderService,
        UpdateGmoEpsilonOrderService $updateGmoEpsilonOrderService,
        CartService $cartService,
        MailService $mailService,
        MailHistoryRepository $mailHistoryRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->requestGetSales2Service = $requestGetSales2Service;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->updateRegularOrderService = $updateRegularOrderService;
        $this->updateGmoEpsilonOrderService = $updateGmoEpsilonOrderService;
        $this->cartService = $cartService;
        $this->mailService = $mailService;
        $this->mailHistoryRepository = $mailHistoryRepository;
    }

    /**
     * 購入完了処理(commit)を行う
     * コンビニ決済はこのRouteを利用しない
     *
     * @Route(
     *     "/shopping/epsilon_payment/complete",
     *     name="eccube_payment_lite4_payment_complete"
     * )
     */
    public function complete(Request $request)
    {
        $transCode = '';
        $orderNumber = '';
        if ($request->query->get('trans_code')) {
            $transCode = $request->query->get('trans_code');
        }
        if ($request->query->get('order_number')) {
            $orderNumber = $request->query->get('order_number');
        }
        /** @var Order $Order */
        $Order = $this
            ->orderRepository
            ->find((int) explode('x', $orderNumber)[0]);
        if (!$Order) {
            throw new NotFoundHttpException();
        }
        // Check the order was completed
        if ($Order->getGmoEpsilonOrderNo()) {
            log_info('[注文確認] 注文が完了しました.', [$Order->getId()]);
            return $this->redirectToRoute('shopping_error');
        }
        $results = $this
            ->requestGetSales2Service
            ->handle($transCode, $orderNumber);
        if ($results['status'] === 'NG') {
            throw new NotFoundHttpException();
        }
        // 受注登録・決済情報登録ボタンより処理を行った場合は、受注登録画面にリダイレクトさせる
        if ($results['route'] === 'eccube_payment_lite4_admin_create_reg_credit_order') {
            return $this->processingWhenCreatingOrder(
                $Order,
                $results['trans_code'],
                $results['order_number']
            );
        }

        // 定期一覧より受注一括作成を行った場合は、もう一度定期受注作成へ
        if ($results['route'] === 'eccube_payment_lite4_admin_regular_index_add') {
            return $this->processingWhenCreatingRegularOrderIndex(
                $Order,
                $results['trans_code'],
                $results['order_number']
            );
        }
        // マイページよりクレジットカードの登録を行った場合は、カード編集画面にリダイレクトさせる
        if ($results['route'] === 'eccube_payment_lite4_mypage_credit_card_edit' ||
            $results['route'] === 'eccube_payment_lite4_mypage_credit_card_new') {
            return $this->redirectToRoute('eccube_payment_lite4_mypage_credit_card_complete');
        }
        // コマンドで定期受注作成時の処理
        if ($results['route'] === 'eccube_payment_lite4_regular_create_command') {
            return $this
                ->processingWhenCreatingOrderInBatch(
                    $Order,
                    $results['trans_code'],
                    $results['order_number']
                );
        }

        // 決済方法に応じたインスタンスを取得
        /** @var PaymentMethodInterface $PaymentMethod */
        if (!is_null($Order->getPayment())) {
            $PaymentMethod = $this->container->get($Order->getPayment()->getMethodClass());
        }
        $PaymentMethod->setOrder($Order);

        if (!is_null($Order->getPayment()) && (preg_match('/Maillink/', $Order->getPayment()->getMethodClass()))) {
            return $this->paymentMaillinkCompletionProcess($Order, $PaymentMethod, $results['payment_code']);
        }

        return $this->paymentCompletionProcess($Order, $PaymentMethod, $request);
    }

    private function processingWhenCreatingOrder(Order $Order, $transCode, $gmoEpsilonOrderNo): Response
    {
        logs('gmo_epsilon')->addInfo('受注登録より登録した受注の購入完了処理(commit) start.');
        $this
            ->updateGmoEpsilonOrderService
            ->updateAfterMakingPayment(
                $Order,
                $transCode,
                $gmoEpsilonOrderNo
            );
        $this->addSuccess(
            'イプシロン決済サービスに受注ID'.$gmoEpsilonOrderNo.'の決済情報を登録しました', 'admin'
        );
        logs('gmo_epsilon')->addInfo('受注登録より登録した受注の購入完了処理(commit) end.');

        return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
    }

    private function processingWhenCreatingRegularOrderIndex(Order $Order, $transCode, $gmoEpsilonOrderNo): Response
    {
        logs('gmo_epsilon')->addInfo('定期受注一覧より登録した受注の購入完了処理(commit) start.');
        $this
            ->updateGmoEpsilonOrderService
            ->updateAfterMakingPayment(
                $Order,
                $transCode,
                $gmoEpsilonOrderNo
            );
        $this->updateRegularOrderService->updateAfterMakingPayment($Order->getRegularOrder());

        $ids = $this->session->get('eccube.admin.regular.order.createRegularIds');
        logs('gmo_epsilon')->addInfo('定期受注一覧より登録した受注の購入完了処理(commit) end.');

        $this->entityManager->commit();

        return $this->forwardToRoute(
            'eccube_payment_lite4_admin_regular_index_add',
            [],
            [
                'ids' => $ids,
                Constant::TOKEN_NAME => $this->csrfTokenManager->getToken(Constant::TOKEN_NAME)->getValue(),
            ]
        );
    }

    private function processingWhenCreatingOrderInBatch($Order, $transCode, $gmoEpsilonOrderNo): Response
    {
        logs('gmo_epsilon')->addInfo('定期受注作成バッチより登録した受注の購入完了処理(commit) start.');
        $this
            ->updateGmoEpsilonOrderService
            ->updateAfterMakingPayment(
                $Order,
                $transCode,
                $gmoEpsilonOrderNo
            );
        $this->updateRegularOrderService->updateAfterMakingPayment($Order->getRegularOrder());
        logs('gmo_epsilon')->addInfo('定期受注作成バッチより登録した受注の購入完了処理(commit) end.');

        return new Response(1);
    }

    private function paymentMaillinkCompletionProcess(Order $Order, $PaymentMethod, $paymentCode): Response
    {
        logs('gmo_epsilon')->addInfo('メールリンク決済の購入完了処理(commit) start.');
        $PaymentMethod->compProcess($paymentCode);
        logs('gmo_epsilon')->addInfo('メールリンク決済の購入完了処理(commit) end.');

        return new Response(1);
    }

    private function paymentCompletionProcess(Order $Order, $PaymentMethod, Request $request): Response
    {
        logs('gmo_epsilon')->addInfo('購入完了処理(commit) start.');
        logs('gmo_epsilon')->addInfo('POST param argument '.print_r($request->getContent(), true));
        // 購入完了処理
        if ($response = $this->executeCheckout($PaymentMethod)) {
            return $response;
        }
        $this->cartService->clear();
        // メール送信
        $MailHistory = $this->mailHistoryRepository->findBy(['Order' => $Order]);
        // メール送信の処理は必ず入れるが、メール履歴（dtb_mail_history）を確認して無ければ送信の判断を入れる。
        if (!$MailHistory) {
            logs('gmo_epsilon')->addInfo('send order mail');
            $this->mailService->sendOrderMail($Order);
        }

        $this->session->set(OrderHelper::SESSION_ORDER_ID, $Order->getId());
        $this->entityManager->flush();

        return $this->redirectToRoute('shopping_complete');
    }

    /**
     * @return RedirectResponse|Response|void
     */
    private function executeCheckout(PaymentMethodInterface $paymentMethod)
    {
        $PaymentResult = $paymentMethod->checkout();
        $response = $PaymentResult->getResponse();
        // PaymentResultがresponseを保持している場合はresponseを返す
        if ($response instanceof Response && ($response->isRedirection() || $response->isSuccessful())) {
            $this->entityManager->flush();
            logs('gmo_epsilon')->addInfo('購入完了処理(commit): PaymentMethod::checkoutより、responseを返却.');

            return $response;
        }

        // エラー時はロールバックして購入エラーとする.
        if (!$PaymentResult->isSuccess()) {
            $this->entityManager->rollback();
            foreach ($PaymentResult->getErrors() as $error) {
                $this->addError($error);
            }
            logs('gmo_epsilon')->addInfo('[注文処理] PaymentMethod::checkoutのエラーのため, 購入エラー画面へ遷移します.', [$PaymentResult->getErrors()]);

            return $this->redirectToRoute('shopping_error');
        }
    }
}
