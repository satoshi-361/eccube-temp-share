<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Mypage;

use Eccube\Common\EccubeConfig;
use Eccube\Event\TemplateEvent;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddNavEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    public function __construct(
        ConfigRepository $configRepository,
        EccubeConfig $eccubeConfig
    ) {
        $this->configRepository = $configRepository;
        $this->eccubeConfig = $eccubeConfig;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Mypage/index.twig' => 'index',
            'Mypage/history.twig' => 'index',
            'Mypage/favorite.twig' => 'index',
            'Mypage/change.twig' => 'index',
            'Mypage/change_complete.twig' => 'index',
            'Mypage/delivery.twig' => 'index',
            'Mypage/withdraw.twig' => 'index',
            'Mypage/delivery_edit.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/edit_credit_card.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_list.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_detail.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_cycle.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_cancel.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_complete.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_next_delivery_date.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_product_quantity.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_resume.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_shipping.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_skip.twig' => 'index',
            '@EccubePaymentLite4/default/Mypage/regular_suspend.twig' => 'index',
        ];
    }

    public function index(TemplateEvent $event)
    {
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        $paymentIds = $Config->getGmoEpsilonPayments()->map(function ($GmoEpsilonPayment) {
            return $GmoEpsilonPayment->getId();
        })->toArray();

        if (count($paymentIds) > 0) {
            if (in_array($this->eccubeConfig['gmo_epsilon']['pay_id']['credit'], $paymentIds) ||
                in_array($this->eccubeConfig['gmo_epsilon']['pay_id']['reg_credit'], $paymentIds)) {
                $event->addSnippet('@EccubePaymentLite4/default/Mypage/Nav/nav_credit_card.twig');
            }
        }

        if ($Config->getRegular()) {
            $event->addSnippet('@EccubePaymentLite4/default/Mypage/Nav/nav_regular_index.twig');
        }
    }
}
