<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Shopping;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\PaymentRepository;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestGetUserInfoService;
use Plugin\EccubePaymentLite4\Service\Method\Credit;
use Plugin\EccubePaymentLite4\Service\Method\Reg_Credit;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormView;

class CreditCardInfoEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var RequestGetUserInfoService
     */
    private $requestGetUserInfoService;
    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;
    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    public function __construct(
        RequestGetUserInfoService $requestGetUserInfoService,
        EccubeConfig $eccubeConfig,
        PaymentRepository $paymentRepository
    ) {
        $this->requestGetUserInfoService = $requestGetUserInfoService;
        $this->eccubeConfig = $eccubeConfig;
        $this->paymentRepository = $paymentRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopping/index.twig' => 'index',
        ];
    }

    public function index(TemplateEvent $event)
    {
        /** @var Order $Order */
        $Order = $event->getParameter('Order');
        if (!is_null($Order->getPayment()) &&
            Credit::class !== $Order->getPayment()->getMethodClass() &&
            Reg_Credit::class !== $Order->getPayment()->getMethodClass()) {
            return;
        }
        // ゲスト購入の場合はクレジットカード情報を表示しない
        if (is_null($Order->getCustomer())) {
            return;
        }
        $results = $this->requestGetUserInfoService->handle($Order->getCustomer()->getId());
        $isRegisteredCreditCard = true;
        // クレジットカード情報が未登録の場合は、登録済みのクレジットカードのラジオボタンを表示しない
        if ($results['status'] === 'NG') {
            $RegCreditPayment = $this->paymentRepository->findOneBy([
                'method_class' => Reg_Credit::class,
            ]);
            /** @var FormView $paymentFormView */
            $paymentFormView = $event->getParameter('form')['Payment'];
            if (!is_null($RegCreditPayment) && !is_null($RegCreditPayment->getId())) {
                $paymentFormView->offsetUnset((string) $RegCreditPayment->getId());
            }
            $isRegisteredCreditCard = false;
        }
        $event->setParameter('isRegisteredCreditCard', $isRegisteredCreditCard);
        if (!$isRegisteredCreditCard) {
            return;
        }
        $event->addSnippet('@EccubePaymentLite4/default/Shopping/credit_card_info.twig');
        $event->setParameter('cardBrand', $results['cardBrand']);
        $event->setParameter('cardNumberMask', $results['cardNumberMask']);
        $event->setParameter('cardExpire', $results['cardExpire']);
    }
}
