<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Mypage;

use Eccube\Event\TemplateEvent;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\MyPageRegularSetting;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Service\IsMypageRegularSettingService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddRegularNavEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var IsMypageRegularSettingService
     */
    private $isMypageRegularSettingService;

    public function __construct(
        ConfigRepository $configRepository,
        IsMypageRegularSettingService $isMypageRegularSettingService
    ) {
        $this->configRepository = $configRepository;
        $this->isMypageRegularSettingService = $isMypageRegularSettingService;
    }

    public static function getSubscribedEvents()
    {
        return [
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

    /**
     * 定期ナビゲーションの表示を行うeventSubscriberクラス
     * 定期ステータスが継続、休止以外の場合は定期ナビゲーションは表示しない
     */
    public function index(TemplateEvent $event)
    {
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        if (!$Config->getRegular()) {
            return;
        }
        /** @var RegularOrder $RegularOrder */
        $RegularOrder = $event->getParameter('RegularOrder');
        $RegularStatus = $RegularOrder->getRegularStatus();
        // Get regular skip flag
        $regularSkipFlg = $RegularOrder->getRegularSkipFlag();
        // 定期ステータスが継続の場合
        if ($RegularStatus->getId() === RegularStatus::CONTINUE) {
            $event->addSnippet('@EccubePaymentLite4/default/Mypage/RegularNav/detail.twig');
            if ($this->isMypageRegularSettingService->handle(MyPageRegularSetting::REGULAR_CYCLE)) {
                $event->addSnippet('@EccubePaymentLite4/default/Mypage/RegularNav/cycle.twig');
            }
            if ($this->isMypageRegularSettingService->handle(MyPageRegularSetting::NEXT_DELIVERY_DATE)) {
                $event->addSnippet('@EccubePaymentLite4/default/Mypage/RegularNav/next_delivery_date.twig');
            }
            // お届け先変更は管理画面で表示、非表示の制御を行わない。
            $event->addSnippet('@EccubePaymentLite4/default/Mypage/RegularNav/shipping.twig');
            if ($this->isMypageRegularSettingService->handle(MyPageRegularSetting::NUMBER_OR_ITEMS)) {
                $event->addSnippet('@EccubePaymentLite4/default/Mypage/RegularNav/product_quantity.twig');
            }
            if ($this->isMypageRegularSettingService->handle(MyPageRegularSetting::SKIP_ONCE) && $regularSkipFlg == 0) {
                $event->addSnippet('@EccubePaymentLite4/default/Mypage/RegularNav/skip.twig');
            }
            if ($this->isMypageRegularSettingService->handle(MyPageRegularSetting::SUSPEND_AND_RESUME)) {
                $event->addSnippet('@EccubePaymentLite4/default/Mypage/RegularNav/suspend.twig');
            }
            if ($this->isMypageRegularSettingService->handle(MyPageRegularSetting::CANCELLATION)) {
                $event->addSnippet('@EccubePaymentLite4/default/Mypage/RegularNav/cancel.twig');
            }

            return;
        }
        // ステータスが休止の場合
        if ($RegularStatus->getId() === RegularStatus::SUSPEND) {
            $event->addSnippet('@EccubePaymentLite4/default/Mypage/RegularNav/detail.twig');
            // お届け先変更は管理画面で表示、非表示の制御を行わない。
            $event->addSnippet('@EccubePaymentLite4/default/Mypage/RegularNav/shipping.twig');

            if ($this->isMypageRegularSettingService->handle(MyPageRegularSetting::NUMBER_OR_ITEMS)) {
                $event->addSnippet('@EccubePaymentLite4/default/Mypage/RegularNav/product_quantity.twig');
            }
            if ($this->isMypageRegularSettingService->handle(MyPageRegularSetting::CANCELLATION)) {
                $event->addSnippet('@EccubePaymentLite4/default/Mypage/RegularNav/cancel.twig');
            }
            if ($this->isMypageRegularSettingService->handle(MyPageRegularSetting::SUSPEND_AND_RESUME)) {
                $event->addSnippet('@EccubePaymentLite4/default/Mypage/RegularNav/resume.twig');
            }
        }
    }
}
