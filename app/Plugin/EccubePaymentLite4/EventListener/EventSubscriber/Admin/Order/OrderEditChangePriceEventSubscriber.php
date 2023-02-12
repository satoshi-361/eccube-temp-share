<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Admin\Order;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestChangePaymentAmountService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestGetEpsilonPaymentInformationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OrderEditChangePriceEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var RequestGetEpsilonPaymentInformationService
     */
    private $requestGetEpsilonPaymentInformationService;
    /**
     * @var RequestChangePaymentAmountService
     */
    private $requestChangePaymentAmountService;
    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;
    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(
        RequestGetEpsilonPaymentInformationService $requestGetEpsilonPaymentInformationService,
        RequestChangePaymentAmountService $requestChangePaymentAmountService,
        EccubeConfig $eccubeConfig,
        SessionInterface $session
    ) {
        $this->requestGetEpsilonPaymentInformationService = $requestGetEpsilonPaymentInformationService;
        $this->requestChangePaymentAmountService = $requestChangePaymentAmountService;
        $this->eccubeConfig = $eccubeConfig;
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
        $results = $this
            ->requestChangePaymentAmountService
            ->handle($TargetOrder);
        // 決済金額変更リクエストが成功すれば、successのメッセージを返す。
        if (!is_null($results) && $results['status'] === 'OK') {
            $this
                ->session
                ->getFlashBag()
                ->add('eccube.admin.success', $results['message']);

            return;
        }
        // 決済金額変更リクエストが失敗すれば、warningのメッセージを返す。
        if (!is_null($results)) {
            $this
                ->session
                ->getFlashBag()
                ->add('eccube.admin.warning', $results['message']);
        }
    }
}
