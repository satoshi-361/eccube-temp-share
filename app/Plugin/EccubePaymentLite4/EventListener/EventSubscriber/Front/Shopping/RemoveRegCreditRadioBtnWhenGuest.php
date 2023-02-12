<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Shopping;

use Eccube\Entity\Order;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\PaymentRepository;
use Plugin\EccubePaymentLite4\Service\Method\Reg_Credit;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormView;

class RemoveRegCreditRadioBtnWhenGuest implements EventSubscriberInterface
{
    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    public function __construct(
        PaymentRepository $paymentRepository
    ) {
        $this->paymentRepository = $paymentRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Shopping/index.twig' => 'index',
        ];
    }

    public function index(TemplateEvent $templateEvent)
    {
        /** @var Order $Order */
        $Order = $templateEvent->getParameter('Order');
        // ゲスト購入の場合は登録済みのクレジットカードのラジオボタンを表示しない
        if (is_null($Order->getCustomer())) {
            $RegCreditPayment = $this->paymentRepository->findOneBy([
                'method_class' => Reg_Credit::class,
            ]);
            /** @var FormView $paymentFormView */
            $paymentFormView = $templateEvent->getParameter('form')['Payment'];
            if (!is_null($RegCreditPayment) && !is_null($RegCreditPayment->getId())) {
                $paymentFormView->offsetUnset((string) $RegCreditPayment->getId());
            }

            return;
        }
    }
}
