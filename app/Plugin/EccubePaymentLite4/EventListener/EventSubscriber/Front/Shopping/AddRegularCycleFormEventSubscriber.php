<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Shopping;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\SaleType;
use Eccube\Entity\Order;
use Eccube\Entity\Shipping;
use Eccube\Event\TemplateEvent;
use Eccube\Service\Payment\Method\Cash;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\RegularCycle;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Repository\RegularCycleRepository;
use Plugin\EccubePaymentLite4\Service\CalculateNextDeliveryDateService;
use Plugin\EccubePaymentLite4\Service\IsRegularPaymentService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AddRegularCycleFormEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;
    /**
     * @var IsRegularPaymentService
     */
    private $isRegularPaymentService;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var RegularCycleRepository
     */
    private $regularCycleRepository;
    /**
     * @var CalculateNextDeliveryDateService
     */
    private $calculateNextDeliveryDateService;

    public function __construct(
        EccubeConfig $eccubeConfig,
        IsRegularPaymentService $isRegularPaymentService,
        ConfigRepository $configRepository,
        SessionInterface $session,
        RegularCycleRepository $regularCycleRepository,
        CalculateNextDeliveryDateService $calculateNextDeliveryDateService
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->isRegularPaymentService = $isRegularPaymentService;
        $this->configRepository = $configRepository;
        $this->session = $session;
        $this->regularCycleRepository = $regularCycleRepository;
        $this->calculateNextDeliveryDateService = $calculateNextDeliveryDateService;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopping/index.twig' => 'index',
            'Shopping/confirm.twig' => 'confirm',
        ];
    }

    public function index(TemplateEvent $event)
    {
        /** @var Order $Order */
        $Order = $event->getParameter('Order');

        /** @var SaleType $SaleType */
        $SaleType = $Order->getShippings()->first()->getDelivery()->getSaleType();

        if ($SaleType->getName() !== '定期商品') {
            return;
        }

        if (!$this->isRegularPaymentService->isRegularPayment($Order)) {
            return;
        }

        $event->addSnippet('@EccubePaymentLite4/default/Shopping/regular_cycle_form.twig');
    }

    public function confirm(TemplateEvent $event)
    {
        /** @var Order $Order */
        $Order = $event->getParameter('Order');
        /** @var FormView $form */
        $form = $event->getParameter('form');

        /** @var SaleType $SaleType */
        $SaleType = $Order->getShippings()->first()->getDelivery()->getSaleType();

        if ($SaleType->getName() !== '定期商品') {
            return;
        }

        if (!$this->isRegularPaymentService->isRegularPayment($Order)) {
            return;
        }
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        /** @var Shipping $Shipping */
        $Shipping = $Order->getShippings()->first();
        if (is_null($Shipping->getShippingDeliveryDate())) {
            $addDay = $Config->getFirstDeliveryDays();
            $firstDeliveryDate = new \DateTime('+'.$Config->getFirstDeliveryDays().' day');
        } else {
            $firstDeliveryDate = $Shipping->getShippingDeliveryDate();
            $addDay = $this->getDiffShippingDeliveryDate($firstDeliveryDate);
        }
        /** @var RegularCycle $RegularCycle */
        $RegularCycle = $this
            ->regularCycleRepository
            ->find($this->session->get('regular_cycle_id'));
        $nextDeliveryDate = $this
            ->calculateNextDeliveryDateService
            ->calc($RegularCycle, $addDay);

        $nextDate = $nextDeliveryDate;
        // get list delivery date
        $nextOrderDate = [];
        $nextOrderDate[0] = clone $firstDeliveryDate;
        $nextOrderDate[1] = clone $nextDeliveryDate;
        $limitCycle = 4;
        for ($i = 2; $i <= $limitCycle; $i++) {
            $nextDeliveryDate = $this->calculateNextDeliveryDateService->calc_delivery($RegularCycle, clone $nextDeliveryDate);
            $nextOrderDate[$i] = $nextDeliveryDate;
        }

        $firstDay = $Config->getFirstDeliveryDays();
        $deadlineDay = $Config->getRegularOrderDeadline();

        // payment is 代金引換
        $isCashPayment = false;
        if (!is_null($Order->getPayment())
            && $Order->getPayment()->getMethodClass() === Cash::class) {
            $isCashPayment = true;
        }

        // Get list product item
        $ProductItems = $Order->getProductOrderItems();
        $event->setParameter('ProductItems', $ProductItems);
        $event->setParameter('limitCycle', 5);
        $event->setParameter('isCashPayment', $isCashPayment);
        $event->setParameter('firstDay', $firstDay);
        $event->setParameter('deadlineDay', $deadlineDay);
        $event->setParameter('nextOrderDate', $nextOrderDate);
        $event->setParameter('label', $form->children['RegularCycles']->vars['data']);
        $event->setParameter('firstDeliveryDate', $firstDeliveryDate);
        $event->setParameter('nextDeliveryDate', $nextDate);
        $event->addSnippet('@EccubePaymentLite4/default/Shopping/regular_cycle_confirm_form.twig');
    }

    private function getDiffShippingDeliveryDate(\DateTime $shippingDeliveryDate): int
    {
        $today = new \DateTime('today');
        $interval = $today->diff($shippingDeliveryDate);

        return (int) $interval->format('%a');
    }
}
