<?php

namespace Plugin\PayPalCheckout;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Eccube\Entity\Block;
use Eccube\Entity\BlockPosition;
use Eccube\Entity\Delivery;
use Eccube\Entity\Layout;
use Eccube\Entity\Master\DeviceType;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Entity\Payment;
use Eccube\Entity\Payment as EccubePayment;
use Eccube\Entity\PaymentOption;
use Eccube\Kernel as EccubeKernel;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\BlockPositionRepository;
use Eccube\Repository\BlockRepository;
use Eccube\Repository\DeliveryRepository;
use Eccube\Repository\LayoutRepository;
use Eccube\Repository\PageLayoutRepository;
use Eccube\Repository\PageRepository;
use Eccube\Repository\PaymentOptionRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Util\CacheUtil;
use Plugin\PayPalCheckout\Entity\Config;
use Plugin\PayPalCheckout\Repository\ConfigRepository;
use Plugin\PayPalCheckout\Service\Method\BankTransfer;
use Plugin\PayPalCheckout\Service\Method\CreditCard;
use Plugin\PayPalCheckout\Service\Method\InlineGuest;
use Plugin\PayPalCheckout\Service\Method\Acdc;
use Plugin\PayPalCheckout\Service\Method\Subscription;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class PluginManager
 * @package Plugin\PayPalCheckout
 */
class PluginManager extends AbstractPluginManager
{
    // レイアウトID：下層ページ用レイアウト
    const LAYOUT_ID_LOWER_PAGE = 2;

    // ページ設定
    const CONFIRM_PAGE_NAME = "商品購入/ご注文確認";
    const CONFIRM_PAGE_URL = "paypal_confirm";
    const CONFIRM_PAGE_FILE_NAME = "Shopping/confirm";
    const PAGE_META_ROBOTS_NOINDEX = "noindex";
    const PAGE_EDIT_TYPE_CONTROLLER = 2;   //controller必要

    // 決済手段
    const PAYMENT_METHODS = [
        CreditCard::class => 'PayPal決済',
        BankTransfer::class => 'かんたん銀行決済(PayPal)',
        InlineGuest::class => 'クレジットカード決済(簡易)',
        Acdc::class => 'クレジットカード決済',
        // プラグインの初期リリース(v1.0.0)には、定期決済は含めない
        // Subscription::class => 'PayPal決済/継続決済',
    ];

    // 決済可能上下限
    const MIN_AMOUNT = 1;
    const MAX_AMOUNT = 1000000;

    /**
     * {@inheritdoc}
     */
    public function install(array $meta, ContainerInterface $container)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function update(array $meta, ContainerInterface $container)
    {
        $this->registerPaymentMethod($container);
    }

    /**
     * @param array $meta
     * @param ContainerInterface $container
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        if ($this->isFirstEnable($container)) {
            $this->registerPluginConfig($container);
        }
        $this->registerPaymentMethod($container);
        $this->registerBrandLogo($container);

        // ショートカット決済のヘッダー・フッターのレイアウトをインストール
        $this->registerConfirmPage($container);
    }

    /**
     * @param array $meta
     * @param ContainerInterface $container
     * @throws ORMException
     * @throws \Exception
     */
    public function disable(array $meta, ContainerInterface $container)
    {
        // ショートカット決済のページレイアウトを削除
        $this->deleteConfirmPage($container);

        $this->disablePaymentMethod($container);

        if ($container->has("kernel")) {
            // PluginManager では EntityManager 以外から DI できないため、直接インスタンス化
            /** @var EccubeKernel $kernel */
            $kernel = $container->get('kernel');
            $cache = new CacheUtil($kernel, $container);
            $cache->clearTwigCache();
            $cache->clearDoctrineCache();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        $this->deleteBrandLogo($container);
    }

    /**
     * 初めてプラグインが有効化される場合か否か
     *
     * @param ContainerInterface $container
     * @return bool
     */
    private function isFirstEnable(ContainerInterface $container): bool
    {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        /** @var ConfigRepository $configRepository */
        $configRepository = $entityManager->getRepository(Config::class);
        /** @var Config $Config */
        $Config = $configRepository->get();

        return empty($Config);
    }

    /**
     * @param ContainerInterface $container
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function registerPluginConfig(ContainerInterface $container): void
    {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get('doctrine')->getManager();

        /** @var Config $Config */
        $Config = Config::createInitialConfig();
        $entityManager->persist($Config);
        $entityManager->flush($Config);
    }

    /**
     * @param ContainerInterface $container
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function registerPaymentMethod(ContainerInterface $container): void
    {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $entityManager->getRepository(Payment::class);

        foreach (self::PAYMENT_METHODS as $paymentMethodClass => $paymentMethodName) {
            // 支払方法追加
            $payment = $paymentRepository->findOneBy(['method_class' => $paymentMethodClass]);
            if (empty($payment)) {
                /** @var EccubePayment $payment */
                $payment = new EccubePayment();
                $payment
                    ->setRuleMin(self::MIN_AMOUNT)
                    ->setRuleMax(self::MAX_AMOUNT)
                    ->setCharge(0)
                    ->setSortNo(1)
                    ->setVisible(true)
                    ->setMethod($paymentMethodName)
                    ->setMethodClass($paymentMethodClass);
            } else {
                // 既に存在していた場合は上下限、「表示」にだけする
                $minRule = $payment->getRuleMin();
                $maxRule = $payment->getRuleMax();
                if (!isset($minRule) || $minRule < self::MIN_AMOUNT)
                    $minRule = self::MIN_AMOUNT;
                if (!isset($maxRule) || $maxRule > self::MAX_AMOUNT)
                    $maxRule = self::MAX_AMOUNT;

                $payment
                    ->setRuleMin($minRule)
                    ->setRuleMax($maxRule)
                    ->setVisible(true);
            }
            $entityManager->persist($payment);
            $entityManager->flush($payment);

            if($paymentMethodClass == Acdc::class) {
                continue;
            }
            // 既存の配送方法設定に支払方法を追加
            /** @var Payment $addedPaymentMethod */
            $addedPaymentMethod = $paymentRepository->findOneBy(['method_class' => $paymentMethodClass]);
            /** @var DeliveryRepository $deliveryRepository */
            $deliveryRepository = $entityManager->getRepository(Delivery::class);
            /** @var Delivery $delivery */
            foreach ($deliveryRepository->findAll() as $delivery) {
                /** @var PaymentOptionRepository $paymentOptionRepository */
                $paymentOptionRepository = $entityManager->getRepository(PaymentOption::class);
                $paymentOption = $paymentOptionRepository->findOneBy([
                    'delivery_id' => $delivery->getId(),
                    'payment_id' => $addedPaymentMethod->getId(),
                ]);
                if (!is_null($paymentOption)) {
                    continue;
                }
                $paymentOption = new PaymentOption();
                $paymentOption
                    ->setPayment($addedPaymentMethod)
                    ->setPaymentId($addedPaymentMethod->getId())
                    ->setDelivery($delivery)
                    ->setDeliveryId($delivery->getId());
                $entityManager->persist($paymentOption);
                $entityManager->flush($paymentOption);
            }
        }
    }

    /**
     * 支払方法を無効にする
     * 消してしまうと既に決済があった場合にリレーションが壊れる(エラーになる)ため
     *
     * @param ContainerInterface $container
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function disablePaymentMethod(ContainerInterface $container): void
    {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $entityManager->getRepository(Payment::class);

        foreach (self::PAYMENT_METHODS as $paymentMethodClass => $paymentMethodName) {
            /** @var Payment $payment */
            $payment = $paymentRepository->findOneBy(['method_class' => $paymentMethodClass]);
            if (empty($payment)) {
                continue;
            }
            $payment->setVisible(false);
            $entityManager->persist($payment);
            $entityManager->flush($payment);
        }
    }

    /**
     * @param ContainerInterface $container
     * @param Block $Block
     * @return string
     */
    private function getBlockFilePathInTheme(ContainerInterface $container, Block $Block): string
    {
        $dir = sprintf('%s/app/template/%s/Block',
            $container->getParameter('kernel.project_dir'),
            $container->getParameter('eccube.theme'));
        return "${dir}/{$Block->getFileName()}.twig";
    }

    /**
     * @param ContainerInterface $container
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    private function registerBrandLogo(ContainerInterface $container): void
    {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        /** @var BlockRepository $blockRepository */
        $blockRepository = $entityManager->getRepository(Block::class);
        /** @var Block $Block */
        $logoBlock = $blockRepository->findOneBy([
            'file_name' => 'paypal_logo',
        ]);
        // 既に存在する場合はスキップ
        if (!is_null($logoBlock)) {
            return;
        }

        /** @var DeviceType $DeviceType */
        $DeviceType = $entityManager->find(DeviceType::class, DeviceType::DEVICE_TYPE_PC);

        /** @var Block $Block */
        $Block = new Block();
        $Block->setName('PayPalロゴ');
        $Block->setFileName('paypal_logo');
        $Block->setDeviceType($DeviceType);
        $Block->setUseController(false);
        $Block->setDeletable(true);
        $entityManager->persist($Block);
        $entityManager->flush($Block);

        /** @var Filesystem $fs */
        $fs = new Filesystem();

        $originBlock = __DIR__ . '/Resource/template/default/Block/paypal_logo.twig';
        $file = $this->getBlockFilePathInTheme($container, $Block);
        $fs->copy($originBlock, $file);

        /** @var BlockPositionRepository $blockPositionRepository */
        $blockPositionRepository = $entityManager->getRepository(BlockPosition::class);

        // ロゴを挿入するページ
        $insertPages = [
            Layout::DEFAULT_LAYOUT_TOP_PAGE,
            Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE,
        ];
        foreach ($insertPages as $insertPage) {
            /** @var BlockPosition $footer */
            $footer = $blockPositionRepository->findOneBy([
                'section' => Layout::TARGET_ID_FOOTER,
                'layout_id' => $insertPage
            ], ['block_row' => 'DESC']);
            if (is_null($footer)) {
                // フッターに何もなければ最初に追加
                $InsertedBlockPosition = 0;
            } else {
                // フッター要素の一番最後に挿入
                $InsertedBlockPosition = $footer->getBlockRow() + 1;
            }

            /** @var Layout $Layout */
            $Layout = $entityManager->find(Layout::class, $insertPage);

            /** @var BlockPosition $BlockPosition */
            $BlockPosition = new BlockPosition();
            $BlockPosition->setSection(Layout::TARGET_ID_FOOTER);
            $BlockPosition->setBlock($Block);
            $BlockPosition->setBlockId($Block->getId());
            $BlockPosition->setLayout($Layout);
            $BlockPosition->setLayoutId($insertPage);
            $BlockPosition->setBlockRow($InsertedBlockPosition);
            $entityManager->persist($BlockPosition);
        }
        $entityManager->flush($BlockPosition);
    }

    private function registerConfirmPage(ContainerInterface $container)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        // Pageが登録済みであれば何もしない
        /** @var PageRepository $pageRepository */
        $pageRepository = $entityManager->getRepository(Page::class);
        $page = $pageRepository->findOneBy(["url" => $this::CONFIRM_PAGE_URL]);
        if (is_null($page) == false) return;

        // 下層ページ用のLayout取得
        /** @var LayoutRepository $layoutRepository */
        $layoutRepository = $entityManager->getRepository(Layout::class);
        $underLayout = $layoutRepository->findOneBy(["id" => $this::LAYOUT_ID_LOWER_PAGE]);

        // dtb_page_layout の次のSortNoを取得する
        /** @var PageLayoutRepository $pageLayoutRepository */
        $pageLayoutRepository = $entityManager->getRepository(PageLayout::class);
        $LastPageLayout = $pageLayoutRepository->findOneBy([], ['sort_no' => 'DESC']);
        $nextSortNo = $LastPageLayout->getSortNo() + 1;

        $entityManager->beginTransaction();

        // Page に確認画面のレコードをINSERT
        $page = $pageRepository->newPage();
        $page->setName($this::CONFIRM_PAGE_NAME)
            ->setUrl($this::CONFIRM_PAGE_URL)
            ->setFileName($this::CONFIRM_PAGE_FILE_NAME)
            ->setEditType($this::PAGE_EDIT_TYPE_CONTROLLER)
            ->setMetaRobots($this::PAGE_META_ROBOTS_NOINDEX);
        $entityManager->persist($page);
        $entityManager->flush($page);

        // PageLayout に確認画面とレイアウトのマッピングをINSERT
        $pageLayout = new PageLayout();
        $pageLayout->setLayout($underLayout)
            ->setLayoutId($underLayout->getId())
            ->setPageId($page->getId())
            ->setSortNo($nextSortNo)
            ->setPage($page);
        $entityManager->persist($pageLayout);
        $entityManager->flush($pageLayout);

        // コミット
        $entityManager->commit();
    }

    private function deleteConfirmPage(ContainerInterface $container)
    {
        // Pageが未登録なら何もしない
        /** @var EntityManager $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        /** @var PageRepository $pageRepository */
        $pageRepository = $entityManager->getRepository(Page::class);
        $page = $pageRepository->findOneBy(["url" => $this::CONFIRM_PAGE_URL]);
        if (is_null($page)) return;

        $entityManager->beginTransaction();

        // Page から確認画面のレコードをDELETE
        $entityManager->remove($page);
        $entityManager->flush($page);

        // DELETE FROM dtb_page_layout WHERE インストール時にINSERTしたページレイアウト
        $pageLayoutRepository = $entityManager->getRepository(PageLayout::class);
        $pageLayout = $pageLayoutRepository->findOneBy(["page_id" => $page->getId()]);
        if (is_null($pageLayout) === false) {
            $entityManager->remove($pageLayout);
            $entityManager->flush($pageLayout);
        }

        // コミット
        $entityManager->commit();
    }

    /**
     * @param ContainerInterface $container
     * @throws ORMException
     */
    private function deleteBrandLogo(ContainerInterface $container): void
    {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        /** @var BlockRepository $blockRepository */
        $blockRepository = $entityManager->getRepository(Block::class);
        /** @var Block $Block */
        $Block = $blockRepository->findOneBy([
            'file_name' => 'paypal_logo',
        ]);

        /** @var string $removePath */
        $removePath = $this->getBlockFilePathInTheme($container, $Block);
        /** @var Filesystem $fs */
        $fs = new Filesystem();
        if ($fs->exists($removePath)) {
            $fs->remove($removePath);
        }

        /** @var BlockPosition $BlockPosition */
        foreach ($Block->getBlockPositions() as $BlockPosition) {
            $Block->removeBlockPosition($BlockPosition);
            $entityManager->remove($BlockPosition);
        }
        $entityManager->remove($Block);
    }
}
