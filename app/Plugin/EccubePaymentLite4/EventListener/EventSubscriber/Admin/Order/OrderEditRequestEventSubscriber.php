<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Admin\Order;

use Eccube\Entity\Order;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Plugin\EccubePaymentLite4\Entity\PaymentStatus;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestCancelPaymentService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestSalesPaymentService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestShippingRegistrationService;
use Plugin\EccubePaymentLite4\Service\Method\Reg_Credit;
use Plugin\EccubePaymentLite4\Service\UpdatePaymentStatusService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OrderEditRequestEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var RequestCancelPaymentService
     */
    private $requestCancelPaymentService;
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var UpdatePaymentStatusService
     */
    private $updatePaymentStatusService;
    /**
     * @var RequestSalesPaymentService
     */
    private $requestSalesPaymentService;
    /**
     * @var RequestShippingRegistrationService
     */
    private $requestShippingRegistrationService;

    public function __construct(
        RequestCancelPaymentService $requestCancelPaymentService,
        RequestSalesPaymentService $requestSalesPaymentService,
        RequestShippingRegistrationService $requestShippingRegistrationService,
        UpdatePaymentStatusService $updatePaymentStatusService,
        SessionInterface $session
    ) {
        $this->requestCancelPaymentService = $requestCancelPaymentService;
        $this->requestSalesPaymentService = $requestSalesPaymentService;
        $this->requestShippingRegistrationService = $requestShippingRegistrationService;
        $this->updatePaymentStatusService = $updatePaymentStatusService;
        $this->session = $session;
    }

    public static function getSubscribedEvents()
    {
        return [
            EccubeEvents::ADMIN_ORDER_EDIT_INDEX_COMPLETE => 'adminOrderEditIndexComplete',
        ];
    }

    public function adminOrderEditIndexComplete(EventArgs $eventArgs)
    {
        /** @var Order $TargetOrder */
        $TargetOrder = $eventArgs->getArgument('TargetOrder');
        // mapped => falseで定義しているため, フォームから決済ステータスを取得する.
        /** @var Form $form */
        $form = $eventArgs->getArgument('form');
        if ($eventArgs->getRequest()->attributes->get('_route') === 'admin_order_new') {
            if ($TargetOrder->getPayment()->getMethodClass() !== Reg_Credit::class) {
                return;
            }
            $this->addWarningMessage('イプシロン決済サービスに登録済みクレジットカード決済登録は行っておりません。「決済を登録する」ボタンより決済処理を完了させてください。');

            return;
        }
        // 決済ステータスが未入力の場合は処理を行なわない。
        if (is_null($form->get('PaymentStatus')->getData())) {
            return;
        }
        /** @var PaymentStatus $PaymentStatus */
        $paymentStatusId = $form->get('PaymentStatus')->getData()->getId();

        // 受注登録（編集）画面
        if ($paymentStatusId === PaymentStatus::CHARGED) {
            $results = $this
                ->requestSalesPaymentService
                ->handle($TargetOrder);
            // リクエストの成否に関わらず、決済ステータスは更新する。
            $this
                ->updatePaymentStatusService
                ->handle($TargetOrder, $paymentStatusId);
            $this->addSuccessMessage('受注情報の決済ステータスを「課金済み」に変更しました');
            if ($results['status'] === 'OK') {
                $this->addSuccessMessage($results['message']);

                return;
            }
            $this->addWarningMessage($results['message']);

            return;
        }
        if ($paymentStatusId === PaymentStatus::CANCEL) {
            $results = $this
                ->requestCancelPaymentService
                ->handle($TargetOrder);
            // リクエストの成否に関わらず決済ステータスを更新する
            $this
                ->updatePaymentStatusService
                ->handle($TargetOrder, $paymentStatusId);
            $this->addSuccessMessage('受注情報の決済ステータスを「キャンセル」に変更しました');
            if ($results['status'] === 'OK') {
                $this->addSuccessMessage($results['message']);

                return;
            }
            $this->addWarningMessage($results['message']);

            return;
        }
        if ($paymentStatusId === PaymentStatus::SHIPPING_REGISTRATION) {
            $results = $this
                ->requestShippingRegistrationService
                ->handle($TargetOrder);
            // リクエストの成否に関わらず決済ステータスを更新する
            $this
                ->updatePaymentStatusService
                ->handle($TargetOrder, $paymentStatusId);
            $this->addSuccessMessage('受注情報の決済ステータスを「出荷登録中」に変更しました');
            if ($results['status'] === 'OK') {
                $this->addSuccessMessage($results['message']);

                return;
            }
            $this->addWarningMessage($results['message']);

            return;
        }
        if ($paymentStatusId === PaymentStatus::TEMPORARY_SALES) {
            $this
                ->updatePaymentStatusService
                ->handle($TargetOrder, $paymentStatusId);
            $this->addSuccessMessage('受注情報の決済ステータスを「仮売上」に変更しました');

            return;
        }
        if ($paymentStatusId === PaymentStatus::UNPAID) {
            $this
                ->updatePaymentStatusService
                ->handle($TargetOrder, $paymentStatusId);
            $this->addSuccessMessage('受注情報の決済ステータスを「未課金」に変更しました');

            return;
        }
        if ($paymentStatusId === PaymentStatus::UNDER_REVIEW) {
            $this
                ->updatePaymentStatusService
                ->handle($TargetOrder, $paymentStatusId);
            $this->addSuccessMessage('受注情報の決済ステータスを「審査中」に変更しました');

            return;
        }
        if ($paymentStatusId === PaymentStatus::EXAMINATION_NG) {
            $this
                ->updatePaymentStatusService
                ->handle($TargetOrder, $paymentStatusId);
            $this->addSuccessMessage('受注情報の決済ステータスを「審査NG」に変更しました');

            return;
        }
    }

    private function addSuccessMessage($message)
    {
        $this
            ->session
            ->getFlashBag()
            ->add('eccube.admin.success', $message);
    }

    private function addWarningMessage($message)
    {
        $this
            ->session
            ->getFlashBag()
            ->add('eccube.admin.warning', $message);
    }
}
