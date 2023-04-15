<?php

namespace Plugin\EccubePaymentLite4;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Layout;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\Master\SaleType;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\LayoutRepository;
use Eccube\Repository\MailTemplateRepository;
use Eccube\Repository\Master\SaleTypeRepository;
use Eccube\Repository\PageLayoutRepository;
use Eccube\Repository\PageRepository;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\ConvenienceStore;
use Plugin\EccubePaymentLite4\Entity\DeliveryCompany;
use Plugin\EccubePaymentLite4\Entity\GmoEpsilonPayment;
use Plugin\EccubePaymentLite4\Entity\MyPageRegularSetting;
use Plugin\EccubePaymentLite4\Entity\PaymentStatus;
use Plugin\EccubePaymentLite4\Entity\RegularCycleType;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Repository\ConvenienceStoreRepository;
use Plugin\EccubePaymentLite4\Repository\DeliveryCompanyRepository;
use Plugin\EccubePaymentLite4\Repository\GmoEpsilonPaymentRepository;
use Plugin\EccubePaymentLite4\Repository\MyPageRegularSettingRepository;
use Plugin\EccubePaymentLite4\Repository\PaymentStatusRepository;
use Plugin\EccubePaymentLite4\Repository\RegularCycleTypeRepository;
use Plugin\EccubePaymentLite4\Repository\RegularStatusRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginManager extends AbstractPluginManager
{
    public function install(array $meta, ContainerInterface $container)
    {
    }

    public function enable(array $meta, ContainerInterface $container)
    {
        $this->createPlgGmoEpsilonConfig($container);
        $this->insertOrUpdateRegularStatus($container);
        $this->registerPage($container);
        $this->insertOrUpdatePaymentStatus($container);
        $this->insertOrUpdateDeliveryCompany($container);
        $this->insertOrUpdateGmoEpsilonPayment($container);
        $this->insertOrUpdateConvenienceStore($container);
        $this->insertOrUpdateMyPageRegularSetting($container);
        $this->insertOrUpdateRegularCycleType($container);
        $this->insertOrUpdateMailTemplate($container);
        $this->insertSaleType($container);
    }

    public function disable(array $meta, ContainerInterface $container)
    {
        $this->removePageAndPageLayout($container);
        $this->removeMailTemplate($container);
    }

    public function uninstall(array $meta, ContainerInterface $container)
    {
    }

    public function update(array $meta, ContainerInterface $container)
    {
        $this->insertOrUpdateRegularStatus($container);
        $this->registerPage($container);
        $this->insertOrUpdatePaymentStatus($container);
        $this->insertOrUpdateDeliveryCompany($container);
        $this->insertOrUpdateGmoEpsilonPayment($container);
        $this->insertOrUpdateConvenienceStore($container);
        $this->insertOrUpdateMyPageRegularSetting($container);
        $this->insertOrUpdateRegularCycleType($container);
        $this->updateGmoEpsilonConfig($container);
        $this->insertOrUpdateMailTemplate($container);
        $this->insertSaleType($container);
    }

    public function createPlgGmoEpsilonConfig(ContainerInterface $container)
    {
        $entityManage = $container->get('doctrine')->getManager();
        $configRepository = $entityManage->getRepository(Config::class);

        $Config = $configRepository->find(1);
        if ($Config) {
            return;
        }

        // プラグイン情報初期セット NULL
        $Config = new Config();
        $Config
            ->setEnvironmentalSetting(Config::ENVIRONMENTAL_SETTING_DEVELOPMENT)
            ->setCreditPaymentSetting(Config::TOKEN_PAYMENT)
            ->setRegular(1)
            ->setCardExpirationNotificationDays(15)
            ->setFirstDeliveryDays(5)
            ->setNextDeliveryDateChangeableRangeDays(5)
            ->setNextDeliveryDaysAtRegularResumption(5)
            ->setNextDeliveryDaysAfterRePayment(5)
            ->setRegularOrderDeadline(5)
            ->setRegularDeliveryNotificationEmailDays(5)
            ->setRegularStoppableCount(5)
            ->setRegularCancelableCount(5)
        ;
        $entityManage->persist($Config);
        $entityManage->flush();
    }

    private function updateGmoEpsilonConfig(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $configRepository = $entityManager->getRepository(Config::class);

        /** @var Config $Config */
        $Config = $configRepository->find(1);

        $GmoEpsilonPayments = $Config->getGmoEpsilonPayments();
        if ($GmoEpsilonPayments->isEmpty()) {
            // シリアライズされたGMOイプシロンの決済種別を登録する
            $usePayments = unserialize($Config->getUsePayment());
            if ($usePayments) {
                foreach ($usePayments as $gmoPaymentId) {
                    $gmoEpsilonPaymentRepository = $entityManager->getRepository(GmoEpsilonPayment::class);
                    /** @var GmoEpsilonPayment $GmoEpsilonPayment */
                    $GmoEpsilonPayment = $gmoEpsilonPaymentRepository->find($gmoPaymentId);
                    $Config->addGmoEpsilonPayment($GmoEpsilonPayment);
                    $entityManager->persist($Config);
                }
                $entityManager->flush();
            }
        }

        $ConvenienceStore = $Config->getConvenienceStores();
        if ($ConvenienceStore->isEmpty()) {
            // シリアライズされたGMOイプシロンのコンビニ種別を登録する
            $convenienceStore = unserialize($Config->getUseConvenience());
            if ($convenienceStore) {
                foreach ($convenienceStore as $convenienceStoreId) {
                    $convenienceStoreRepository = $entityManager->getRepository(ConvenienceStoreRepository::class);
                    /** @var ConvenienceStore $ConvenienceStore */
                    $ConvenienceStore = $convenienceStoreRepository->find($convenienceStoreId);
                    $Config->addConvenienceStores($ConvenienceStore);
                    $entityManager->persist($Config);
                }
                $entityManager->flush();
            }
        }
    }

    private function insertOrUpdateRegularStatus($container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $regularStatusRepository = $entityManager->getRepository(RegularStatus::class);
        foreach ($this->regularStatusData() as $value) {
            /** @var RegularStatus $RegularStatus */
            $RegularStatus = $regularStatusRepository->find($value[0]);
            if (is_null($RegularStatus)) {
                $RegularStatus = new RegularStatus();
            }
            $RegularStatus
                ->setId($value[0])
                ->setName($value[1])
                ->setSortNo($value[2]);
            $entityManager->persist($RegularStatus);
        }
        $entityManager->flush();
    }

    private function regularStatusData()
    {
        return [
            [RegularStatus::CONTINUE, RegularStatus::CONTINUE_NAME, 1],
            [RegularStatus::CANCELLATION, RegularStatus::CANCELLATION_NAME, 2],
            [RegularStatus::SUSPEND, RegularStatus::SUSPEND_NAME, 3],
            [RegularStatus::PAYMENT_ERROR, RegularStatus::PAYMENT_ERROR_NAME, 4],
            [RegularStatus::SYSTEM_ERROR, RegularStatus::SYSTEM_ERROR_NAME, 5],
            [RegularStatus::WAITING_RE_PAYMENT, RegularStatus::WAITING_RE_PAYMENT_NAME, 6],
            [RegularStatus::CANCELLATION_EXPIRED_RESUMPTION, RegularStatus::CANCELLATION_EXPIRED_RESUMPTION_NAME, 6],
        ];
    }

    private function registerPage(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();

        $pageRepository = $entityManager->getRepository(Page::class);

        $pageLayoutRepository = $entityManager->getRepository(PageLayout::class);

        $layoutRepository = $entityManager->getRepository(Layout::class);

        /** @var Layout $layoutUnderLayer */
        $layoutUnderLayer = $layoutRepository->find(Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);

        foreach ($this->getPageData() as $pageData) {
            /** @var Page $page */
            $page = $pageRepository->findOneBy([
                'url' => $pageData['url'],
            ]);
            if (is_null($page)) {
                $page = new Page();
            }
            $page
                ->setName($pageData['name'])
                ->setUrl($pageData['url'])
                ->setFileName($pageData['file_name'])
                ->setMetaRobots($pageData['meta_robots'])
                ->setEditType($pageData['edit_type']);
            $entityManager->persist($page);
            $entityManager->flush();
            /** @var PageLayout $pageLayout */
            $pageLayout = $pageLayoutRepository->findOneBy([
                'page_id' => $page->getId(),
                'layout_id' => Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE,
            ]);
            if (is_null($pageLayout)) {
                $pageLayout = new PageLayout();
            }
            /** @var PageLayout $pageLayoutLastSortNo */
            $pageLayoutLastSortNo = $pageLayoutRepository->findOneBy([], [
                'sort_no' => 'desc',
            ]);
            $pageLayout
                ->setPage($page)
                ->setPageId($page->getId())
                ->setLayout($layoutUnderLayer)
                ->setLayoutId($layoutUnderLayer->getId())
                ->setSortNo($pageLayoutLastSortNo->getSortNo() + 1);
            $entityManager->persist($pageLayout);
            $entityManager->flush();
        }
    }

    private function getPageData()
    {
        return [
            [
                'name' => 'イプシロン決済プラグイン マイページ/カード編集',
                'url' => 'eccube_payment_lite4_mypage_credit_card_index',
                'file_name' => 'EccubePaymentLite4/Resource/template/default/Mypage/edit_credit_card',
                'edit_type' => Page::EDIT_TYPE_DEFAULT,
                'meta_robots' => 'noindex',
            ],
            [
                'name' => 'イプシロン決済プラグイン トークン決済クレジットカード入力',
                'url' => 'eccube_payment_lite4_credit_card_for_token_payment',
                'file_name' => 'EccubePaymentLite4/Resource/template/default/Shopping/credit_card_for_token_payment',
                'edit_type' => Page::EDIT_TYPE_DEFAULT,
                'meta_robots' => 'noindex',
            ],
            [
                'name' => 'イプシロン決済プラグイン マイページ/定期受注編集完了',
                'url' => 'eccube_payment_lite4_mypage_regular_complete',
                'file_name' => 'EccubePaymentLite4/Resource/template/default/Mypage/regular_complete',
                'edit_type' => Page::EDIT_TYPE_DEFAULT,
                'meta_robots' => 'noindex',
            ],
            [
                'name' => 'イプシロン決済プラグイン マイページ/定期受注解約',
                'url' => 'eccube_payment_lite4_mypage_regular_suspend',
                'file_name' => 'EccubePaymentLite4/Resource/template/default/Mypage/regular_suspend',
                'edit_type' => Page::EDIT_TYPE_DEFAULT,
                'meta_robots' => 'noindex',
            ],
            [
                'name' => 'イプシロン決済プラグイン マイページ/定期受注休止',
                'url' => 'eccube_payment_lite4_mypage_regular_cancel',
                'file_name' => 'EccubePaymentLite4/Resource/template/default/Mypage/regular_cancel',
                'edit_type' => Page::EDIT_TYPE_DEFAULT,
                'meta_robots' => 'noindex',
            ],
            [
                'name' => 'イプシロン決済プラグイン マイページ/定期受注再開',
                'url' => 'eccube_payment_lite4_mypage_regular_resume',
                'file_name' => 'EccubePaymentLite4/Resource/template/default/Mypage/regular_resume',
                'edit_type' => Page::EDIT_TYPE_DEFAULT,
                'meta_robots' => 'noindex',
            ],
            [
                'name' => 'イプシロン決済プラグイン マイページ/定期サイクル変更',
                'url' => 'eccube_payment_lite4_mypage_regular_cycle',
                'file_name' => 'EccubePaymentLite4/Resource/template/default/Mypage/regular_cycle',
                'edit_type' => Page::EDIT_TYPE_DEFAULT,
                'meta_robots' => 'noindex',
            ],
            [
                'name' => 'イプシロン決済プラグイン マイページ/お届け予定日変更',
                'url' => 'eccube_payment_lite4_mypage_regular_next_delivery_date',
                'file_name' => 'EccubePaymentLite4/Resource/template/default/Mypage/regular_next_delivery_date',
                'edit_type' => Page::EDIT_TYPE_DEFAULT,
                'meta_robots' => 'noindex',
            ],
            [
                'name' => 'イプシロン決済プラグイン マイページ/お届け商品数変更',
                'url' => 'eccube_payment_lite4_mypage_regular_product_quantity',
                'file_name' => 'EccubePaymentLite4/Resource/template/default/Mypage/regular_product_quantity',
                'edit_type' => Page::EDIT_TYPE_DEFAULT,
                'meta_robots' => 'noindex',
            ],
            [
                'name' => 'イプシロン決済プラグイン マイページ/定期受注スキップ',
                'url' => 'eccube_payment_lite4_mypage_regular_skip',
                'file_name' => 'EccubePaymentLite4/Resource/template/default/Mypage/regular_skip',
                'edit_type' => Page::EDIT_TYPE_DEFAULT,
                'meta_robots' => 'noindex',
            ],
            [
                'name' => 'イプシロン決済プラグイン マイページ/定期お届け先変更',
                'url' => 'eccube_payment_lite4_mypage_regular_shipping',
                'file_name' => 'EccubePaymentLite4/Resource/template/default/Mypage/regular_shipping',
                'edit_type' => Page::EDIT_TYPE_DEFAULT,
                'meta_robots' => 'noindex',
            ],
            [
                'name' => 'イプシロン決済プラグイン マイページ/定期一覧',
                'url' => 'eccube_payment_lite4_mypage_regular_list',
                'file_name' => 'EccubePaymentLite4/Resource/template/default/Mypage/regular_list',
                'edit_type' => Page::EDIT_TYPE_DEFAULT,
                'meta_robots' => 'noindex',
            ],
            [
                'name' => 'イプシロン決済プラグイン マイページ/定期購入詳細',
                'url' => 'eccube_payment_lite4_mypage_regular_detail',
                'file_name' => 'EccubePaymentLite4/Resource/template/default/Mypage/regular_detail',
                'edit_type' => Page::EDIT_TYPE_DEFAULT,
                'meta_robots' => 'noindex',
            ],
        ];
    }

    private function removePageAndPageLayout(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $pageRepository = $entityManager->getRepository(Page::class);

        /* @var Page $deletePage */
        foreach ($this->getPageData() as $pageData) {
            $deletePage = $pageRepository->findOneBy(['url' => $pageData['url']]);
            if (is_null($deletePage)) {
                continue;
            }
            $entityManager->remove($deletePage);
        }
        $entityManager->flush();
    }

    private function insertOrUpdatePaymentStatus(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $paymentStatusRepository = $entityManager->getRepository(PaymentStatus::class);

        foreach ($this->paymentStatusData() as $value) {
            /** @var PaymentStatus $paymentStatus */
            $paymentStatus = $paymentStatusRepository
                ->find($value[0]);
            if (is_null($paymentStatus)) {
                $paymentStatus = new PaymentStatus();
            }
            $paymentStatus
                ->setId($value[0])
                ->setName($value[1])
                ->setSortNo($value[2]);
            $entityManager->persist($paymentStatus);
        }
        $entityManager->flush();
    }

    private function paymentStatusData()
    {
        return [
            [PaymentStatus::UNPAID, '未課金', 1],
            [PaymentStatus::CHARGED, '課金済み', 2],
            [PaymentStatus::UNDER_REVIEW, '審査中', 3],
            [PaymentStatus::TEMPORARY_SALES, '仮売上', 4],
            [PaymentStatus::SHIPPING_REGISTRATION, '出荷登録中', 5],
            [PaymentStatus::CANCEL, 'キャンセル', 6],
            [PaymentStatus::EXAMINATION_NG, '審査NG', 7],
        ];
    }

    private function insertOrUpdateDeliveryCompany(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $deliveryCompanyRepository = $entityManager->getRepository(DeliveryCompany::class);

        foreach ($this->deliveryCompanyData() as $value) {
            /** @var DeliveryCompany $deliveryCompany */
            $DeliveryCompany = $deliveryCompanyRepository
                ->find($value[0]);
            if (is_null($DeliveryCompany)) {
                $DeliveryCompany = new DeliveryCompany();
            }
            $DeliveryCompany
                ->setId($value[0])
                ->setName($value[1])
                ->setSortNo($value[2]);
            $entityManager->persist($DeliveryCompany);
        }
        $entityManager->flush();
    }

    private function deliveryCompanyData()
    {
        return [
            [DeliveryCompany::SAGAWA, DeliveryCompany::SAGAWA_NAME, 1],
            [DeliveryCompany::YAMATO, DeliveryCompany::YAMATO_NAME, 2],
            [DeliveryCompany::SEINO, DeliveryCompany::SEINO_NAME, 3],
            [DeliveryCompany::REGISTERED_MAIL__SPECIFIC_RECORD_MAIL, DeliveryCompany::REGISTERED_MAIL__SPECIFIC_RECORD_MAIL_NAME, 4],
            [DeliveryCompany::YUPACK__EXPACK__POST_PACKET, DeliveryCompany::YUPACK__EXPACK__POST_PACKET_NAME, 5],
            [DeliveryCompany::FUKUYAMA, DeliveryCompany::FUKUYAMA_NAME, 6],
            [DeliveryCompany::ECOHAI, DeliveryCompany::ECOHAI_NAME, 7],
            [DeliveryCompany::TEN_FLIGHT__LETTER_PACK__NEW_LIMITED_EXPRESS_MAIL__YU_PACKET, DeliveryCompany::TEN_FLIGHT__LETTER_PACK__NEW_LIMITED_EXPRESS_MAIL__YU_PACKET_NAME, 8],
        ];
    }

    private function insertOrUpdateGmoEpsilonPayment(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $gmoEpsilonPaymentRepository = $entityManager->getRepository(GmoEpsilonPayment::class);

        foreach ($this->gmoEpsilonPaymentData() as $value) {
            /** @var GmoEpsilonPayment $GmoEpsilonPayment */
            $GmoEpsilonPayment = $gmoEpsilonPaymentRepository
                ->find($value[0]);
            if (is_null($GmoEpsilonPayment)) {
                $GmoEpsilonPayment = new GmoEpsilonPayment();
            }
            $GmoEpsilonPayment
                ->setId($value[0])
                ->setName($value[1])
                ->setSortNo($value[2])
                ->setCharge($value[3])
                ->setRuleMax($value[4])
                ->setRuleMin($value[5])
                ->setMethodClass($value[6]);
            $entityManager->persist($GmoEpsilonPayment);
        }
        $entityManager->flush();
    }

    private function gmoEpsilonPaymentData()
    {
        return [
            [GmoEpsilonPayment::CREDIT, 'クレジットカード決済', 1, 0.00, 9999999, 1, 'Plugin\\EccubePaymentLite4\\Service\\Method\\Credit'],
            [GmoEpsilonPayment::REGISTERED_CREDIT_CARD, '登録済みのクレジットカードで決済', 2, 0.00, 9999999, 1, 'Plugin\\EccubePaymentLite4\\Service\\Method\\Reg_Credit'],
            [GmoEpsilonPayment::CONVENIENCE_STORE, 'コンビニ決済', 3, 0.00, 299999, 1, 'Plugin\\EccubePaymentLite4\\Service\\Method\\Conveni'],
            [GmoEpsilonPayment::ONLINE_BANK_JAPAN_NET_BANK, 'ネット銀行決済(PayPay銀行)', 4, 0.00, 9999999, 1, 'Plugin\\EccubePaymentLite4\\Service\\Method\\Netbank_Jnb'],
            [GmoEpsilonPayment::ONLINE_BANK_RAKUTEN, 'ネット銀行決済(楽天銀行)', 5, 0.00, 9999999, 1, 'Plugin\\EccubePaymentLite4\\Service\\Method\\Netbank_Rakuten'],
            [GmoEpsilonPayment::PAY_EASY, 'ペイジー決済', 6, 0.00, 9999999, 1, 'Plugin\\EccubePaymentLite4\\Service\\Method\\Payeasy'],
            [GmoEpsilonPayment::WEB_MONEY, 'WebMoney決済', 7, 0.00, 199999, 1, 'Plugin\\EccubePaymentLite4\\Service\\Method\\Webmoney'],
            [GmoEpsilonPayment::YAHOO_WALLET, 'Yahoo!ウォレット決済サービス', 8, 0.00, 499999, 1, 'Plugin\\EccubePaymentLite4\\Service\\Method\\Ywallet'],
            [GmoEpsilonPayment::PAYPAL, 'Paypal決済', 9, 0.00, 499999, 1, 'Plugin\\EccubePaymentLite4\\Service\\Method\\Paypal'],
            [GmoEpsilonPayment::BIT_CASH, 'Bitcash決済', 10, 0.00, 199999, 1, 'Plugin\\EccubePaymentLite4\\Service\\Method\\Bitcash'],
            [GmoEpsilonPayment::CHOCOM_E_MONEY, '電子マネーちょコム決済', 11, 0.00, 99999, 1, 'Plugin\\EccubePaymentLite4\\Service\\Method\\Chocom'],
            [GmoEpsilonPayment::SMARTPHONE_CARRIER, 'スマートフォンキャリア決済', 12, 0.00, 50000, 1, 'Plugin\\EccubePaymentLite4\\Service\\Method\\Sphone'],
            [GmoEpsilonPayment::JCB_PREMO, 'JCB PREMO', 13, 0.00, 500000, 1, 'Plugin\\EccubePaymentLite4\\Service\\Method\\Jcb'],
            [GmoEpsilonPayment::ONLINE_BANK_SUMISHIN_SBI, '住信SBIネット銀行決済', 14, 0.00, 9999999, 1, 'Plugin\\EccubePaymentLite4\\Service\\Method\\Sumishin'],
            [GmoEpsilonPayment::GMO_DEFERRED_PAYMENT, 'GMO後払い', 15, 0.00, 49999, 1, 'Plugin\\EccubePaymentLite4\\Service\\Method\\Deferred'],
            [GmoEpsilonPayment::VIRTUAL_ACCOUNT, 'バーチャル口座', 16, 0.00, 9999999, 1, 'Plugin\\EccubePaymentLite4\\Service\\Method\\Virtual_Account'],
            [GmoEpsilonPayment::PAYPAY, 'Paypay', 17, 0.00, 9999999, 1, 'Plugin\\EccubePaymentLite4\\Service\\Method\\Paypay'],
            [GmoEpsilonPayment::MAIL_LINK, 'メールリンク決済', 18, 0.00, 9999999, 1, 'Plugin\\EccubePaymentLite4\\Service\\Method\\Maillink'],
        ];
    }

    private function insertOrUpdateConvenienceStore(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $convenienceStoreRepository = $entityManager->getRepository(ConvenienceStore::class);

        foreach ($this->convenienceStoreData() as $value) {
            /** @var ConvenienceStore $ConvenienceStore */
            $ConvenienceStore = $convenienceStoreRepository
                ->find($value[0]);
            if (is_null($ConvenienceStore)) {
                $ConvenienceStore = new ConvenienceStore();
            }
            $ConvenienceStore
                ->setId($value[0])
                ->setName($value[1])
                ->setConveniCode($value[2])
                ->setSortNo($value[3]);
            $entityManager->persist($ConvenienceStore);
        }
        $entityManager->flush();
    }

    private function convenienceStoreData()
    {
        return [
            [ConvenienceStore::SEVEN_ELEVEN, ConvenienceStore::SEVEN_ELEVEN_NAME, 11, 1],
            [ConvenienceStore::FAMILY_MART, ConvenienceStore::FAMILY_MART_NAME, 21, 2],
            [ConvenienceStore::LAWSON, ConvenienceStore::LAWSON_NAME, 31, 3],
            [ConvenienceStore::SEICO_MART, ConvenienceStore::SEICO_MART_NAME, 32, 4],
            [ConvenienceStore::MINI_STOP, ConvenienceStore::MINI_STOP_NAME, 33, 5],
        ];
    }

    private function insertOrUpdateMyPageRegularSetting(ContainerInterface $container)
    {
        $entityManager = $entityManager = $container->get('doctrine')->getManager();
        $myPageRegularSettingRepository = $entityManager->getRepository(MyPageRegularSetting::class);

        foreach ($this->myPageRegularSettingData() as $value) {
            /** @var MyPageRegularSetting $MyPageRegularSetting */
            $MyPageRegularSetting = $myPageRegularSettingRepository
                ->find($value[0]);
            if (is_null($MyPageRegularSetting)) {
                $MyPageRegularSetting = new MyPageRegularSetting();
            }
            $MyPageRegularSetting
                ->setId($value[0])
                ->setName($value[1])
                ->setSortNo($value[2]);
            $entityManager->persist($MyPageRegularSetting);
        }
        $entityManager->flush();
    }

    private function myPageRegularSettingData()
    {
        return [
            [MyPageRegularSetting::REGULAR_CYCLE, '定期サイクル変更', 1],
            [MyPageRegularSetting::NEXT_DELIVERY_DATE, '次回お届け予定日変更', 2],
            [MyPageRegularSetting::NUMBER_OR_ITEMS, '商品数変更', 3],
            [MyPageRegularSetting::CANCELLATION, '解約', 4],
            [MyPageRegularSetting::SUSPEND_AND_RESUME, '休止・再開', 5],
            [MyPageRegularSetting::SKIP_ONCE, '1回スキップ', 6],
        ];
    }

    private function insertOrUpdateRegularCycleType(ContainerInterface $container)
    {
        $entityManager = $entityManager = $container->get('doctrine')->getManager();
        $regularCycleTypeRepository = $entityManager->getRepository(RegularCycleType::class);

        foreach ($this->regularCycleTypeData() as $value) {
            /** @var RegularCycleType $RegularCycleType */
            $RegularCycleType = $regularCycleTypeRepository
                ->find($value[0]);
            if (is_null($RegularCycleType)) {
                $RegularCycleType = new RegularCycleType();
            }
            $RegularCycleType
                ->setId($value[0])
                ->setName($value[1])
                ->setSortNo($value[2]);
            $entityManager->persist($RegularCycleType);
        }
        $entityManager->flush();
    }

    private function regularCycleTypeData()
    {
        return [
            [RegularCycleType::REGULAR_DAILY_CYCLE, '日ごと', 1],
            [RegularCycleType::REGULAR_WEEKLY_CYCLE, '週ごと', 2],
            [RegularCycleType::REGULAR_MONTHLY_CYCLE, '月ごと', 3],
            [RegularCycleType::REGULAR_SPECIFIC_DAY_CYCLE, '特定の日付', 4],
            [RegularCycleType::REGULAR_SPECIFIC_WEEK_CYCLE, '特定の曜日', 5],
        ];
    }

    private function insertOrUpdateMailTemplate(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $mailTemplateRepository = $entityManager->getRepository(MailTemplate::class);

        foreach ($this->mailTemplateData() as $value) {
            /** @var MailTemplate $MailTemplate */
            $MailTemplate = $mailTemplateRepository
                ->findOneBy([
                    'name' => $value[0],
                ]);
            if (is_null($MailTemplate)) {
                $MailTemplate = new MailTemplate();
            }
            $MailTemplate
                ->setName($value[0])
                ->setFileName($value[1])
                ->setMailSubject($value[2]);
            $entityManager->persist($MailTemplate);
        }
        $entityManager->flush();
    }

    private function mailTemplateData()
    {
        return [
            [
                'クレジットカード有効期限通知メール',
                'EccubePaymentLite4/Resource/template/default/Mail/expiration_notice_mail.twig',
                'ご登録されているクレジットカードの有効期限について',
            ],
            [
                '定期購入事前お知らせメール',
                'EccubePaymentLite4/Resource/template/default/Mail/regular_notice_mail.twig',
                'ご購入頂いております定期購入商品についてのお知らせ',
            ],
            [
                '定期指定回数お知らせメール',
                'EccubePaymentLite4/Resource/template/default/Mail/specified_count_notice_mail.twig',
                '定期購入商品継続のお礼',
            ],
        ];
    }

    private function removeMailTemplate(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $mailTemplateRepository = $entityManager->getRepository(MailTemplate::class);

        foreach ($this->mailTemplateData() as $value) {
            /** @var MailTemplate $MailTemplate */
            $MailTemplate = $mailTemplateRepository
                ->findOneBy([
                    'name' => $value[0],
                ]);
            if (!is_null($MailTemplate)) {
                $entityManager->remove($MailTemplate);
            }
        }
        $entityManager->flush();
    }

    private function insertSaleType(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $saleTypeRepository = $entityManager->getRepository(SaleType::class);

        /** @var SaleType $SaleType */
        $SaleType = $saleTypeRepository->findOneBy([
            'name' => '定期商品',
        ]);
        /** @var SaleType $LastSortNoSaleType */
        $LastSortNoSaleType = $saleTypeRepository->findOneBy([], [
            'sort_no' => 'DESC',
        ]);
        $LastIdSaleType = $saleTypeRepository->findOneBy([], [
            'id' => 'DESC',
        ]);
        if (is_null($SaleType)) {
            $SaleType = new SaleType();
            $SaleType
                ->setId($LastIdSaleType->getId() + 1)
                ->setName('定期商品')
                ->setSortNo($LastSortNoSaleType->getSortNo() + 1);
            $entityManager->persist($SaleType);
            $entityManager->flush();
        }
    }
}
