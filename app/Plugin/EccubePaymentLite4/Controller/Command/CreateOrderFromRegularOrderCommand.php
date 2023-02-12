<?php

namespace Plugin\EccubePaymentLite4\Controller\Command;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Service\Payment\Method\Cash;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\PaymentStatus;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularOrderItem;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Repository\RegularOrderRepository;
use Plugin\EccubePaymentLite4\Repository\RegularStatusRepository;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestCard3Service;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestCreateRegularOrderService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestGetSales2Service;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestGetUserInfoService;
use Plugin\EccubePaymentLite4\Service\IsActiveRegularService;
use Plugin\EccubePaymentLite4\Service\IsExpireCreditCardService;
use Plugin\EccubePaymentLite4\Service\IsResumeRegularOrder;
use Plugin\EccubePaymentLite4\Service\Mail\OrderCreationBatchResultMailService;
use Plugin\EccubePaymentLite4\Service\Mail\RegularSpecifiedCountNotificationMailService;
use Plugin\EccubePaymentLite4\Service\Method\Credit;
use Plugin\EccubePaymentLite4\Service\Method\Reg_Credit;
use Plugin\EccubePaymentLite4\Service\RegularCreditService;
use Plugin\EccubePaymentLite4\Service\UpdateNormalPaymentOrderService;
use Plugin\EccubePaymentLite4\Service\UpdateRegularOrderService;
use Plugin\EccubePaymentLite4\Service\UpdateRegularStatusService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class CreateOrderFromRegularOrderCommand extends Command
{
    protected static $defaultName = 'gmo_epsilon_4:regular:create';

    /**
     * @var RegularCreditService
     */
    protected $regularCreditService;

    /**
     * @var RegularOrderRepository
     */
    protected $regularOrderRepository;
    /**
     * @var SymfonyStyle
     */
    private $io;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var RequestCreateRegularOrderService
     */
    private $requestCreateRegularOrderService;
    /**
     * @var RequestCard3Service
     */
    private $requestCard3Service;
    /**
     * @var UpdateRegularOrderService
     */
    private $updateRegularOrderService;
    /**
     * @var RegularStatusRepository
     */
    private $regularStatusRepository;
    /**
     * @var OrderCreationBatchResultMailService
     */
    private $orderCreationBatchResultMailService;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var UpdateNormalPaymentOrderService
     */
    private $updateNormalPaymentOrderService;
    /**
     * @var IsActiveRegularService
     */
    private $isActiveRegularService;
    /**
     * @var IsResumeRegularOrder
     */
    private $isResumeRegularOrder;
    /**
     * @var RequestGetUserInfoService
     */
    private $requestGetUserInfoService;
    /**
     * @var IsExpireCreditCardService
     */
    private $isExpireCreditCardService;
    /**
     * @var UpdateRegularStatusService
     */
    private $updateRegularStatusService;
    /**
     * @var RegularSpecifiedCountNotificationMailService
     */
    private $regularSpecifiedCountNotificationMailService;
    /**
     * @var RequestGetSales2Service
     */
    private $requestGetSales2Service;

    public function __construct(
        RegularCreditService $regularCreditService,
        RegularOrderRepository $regularOrderRepository,
        ConfigRepository $configRepository,
        RequestCreateRegularOrderService $requestCreateRegularOrderService,
        RequestCard3Service $requestCard3Service,
        RegularStatusRepository $regularStatusRepository,
        UpdateRegularOrderService $updateRegularOrderService,
        UpdateNormalPaymentOrderService $updateNormalPaymentOrderService,
        OrderCreationBatchResultMailService $orderCreationBatchResultMailService,
        EntityManagerInterface $entityManager,
        IsActiveRegularService $isActiveRegularService,
        IsResumeRegularOrder $isResumeRegularOrder,
        RequestGetUserInfoService $requestGetUserInfoService,
        IsExpireCreditCardService $isExpireCreditCardService,
        UpdateRegularStatusService $updateRegularStatusService,
        RegularSpecifiedCountNotificationMailService $regularSpecifiedCountNotificationMailService,
        RequestGetSales2Service $requestGetSales2Service
    ) {
        parent::__construct();

        $this->regularCreditService = $regularCreditService;
        $this->regularOrderRepository = $regularOrderRepository;
        $this->configRepository = $configRepository;
        $this->requestCreateRegularOrderService = $requestCreateRegularOrderService;
        $this->requestCard3Service = $requestCard3Service;
        $this->regularStatusRepository = $regularStatusRepository;
        $this->updateRegularOrderService = $updateRegularOrderService;
        $this->updateNormalPaymentOrderService = $updateNormalPaymentOrderService;
        $this->orderCreationBatchResultMailService = $orderCreationBatchResultMailService;
        $this->entityManager = $entityManager;
        $this->isActiveRegularService = $isActiveRegularService;
        $this->isResumeRegularOrder = $isResumeRegularOrder;
        $this->requestGetUserInfoService = $requestGetUserInfoService;
        $this->isExpireCreditCardService = $isExpireCreditCardService;
        $this->updateRegularStatusService = $updateRegularStatusService;
        $this->regularSpecifiedCountNotificationMailService = $regularSpecifiedCountNotificationMailService;
        $this->requestGetSales2Service = $requestGetSales2Service;
    }

    protected function configure()
    {
        $this->setDescription('Regular order batch process');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @return int|void|null
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->isActiveRegularService->isActive()) {
            $this->io->text('=== Regular setting is not Active. ===');

            return;
        }
        $this->io->text('=== RegularOrder Batch start. ===');
        logs('gmo_epsilon')->addInfo('=== RegularOrder Batch start. ===');
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        // 休止再開期間が過ぎている受注を解約(再開期限切れ)とする。
        $this->checkRegularSuspendExpired();

        $deadLineStartDate = new DateTime('today');
        $deadLineStartDate->modify('+'.$Config->getRegularOrderDeadline().' day');
        $deadLineEndDate = new DateTime('tomorrow');
        $deadLineEndDate->modify('+'.$Config->getRegularOrderDeadline().' day');
        /** @var RegularOrder[] $RegularOrders */
        $RegularOrders = $this
            ->regularOrderRepository
            ->getRegularOrdersForCommand(clone $deadLineStartDate, clone $deadLineEndDate);

        $SystemErrorRegularOrders = [];
        $PaymentErrorRegularOrders = [];
        $SuccessRegularOrders = [];
        $RegularOrderSpecifiedList = [];
        foreach ($RegularOrders as $RegularOrder) {
            // 商品が存在するかチェック
            if (!$this->isExistProductClass($RegularOrder)) {
                $SystemErrorRegularOrders[] = $RegularOrder;
                $this->changeRegularStatus($RegularOrder, RegularStatus::SYSTEM_ERROR);
                continue;
            }
            $Order = $this
                ->regularCreditService
                ->createOrder($RegularOrder);
            $regularSpecifiedCountNotification = $Config->getRegularSpecifiedCountNotificationMail();
            // 定期回数［regular_order_count］＋１ の場合定期指定回数お知らせメールを注文者へ送信する
            if ($regularSpecifiedCountNotification === $RegularOrder->getRegularOrderCount() + 1) {
                $RegularOrderSpecifiedList[] = $RegularOrder;
            }
            // 定期受注マスタを最新化
            $this->io->text('=== Update regularOrder start ===');
            $this->updateRegularOrderService->update($RegularOrder, $Order);
            $this->io->text('=== Update regularOrder end ===');

            if (!is_null($Order->getPayment()) &&
                ($Order->getPayment()->getMethodClass() === Credit::class ||
                $Order->getPayment()->getMethodClass() === Reg_Credit::class)) {
                $userInfoResults = $this->requestGetUserInfoService->handle($Order->getCustomer()->getId());
                if ($userInfoResults['status'] === 'NG') {
                    $this->changeRegularStatus($RegularOrder, RegularStatus::PAYMENT_ERROR);
                    $PaymentErrorRegularOrders[] = $RegularOrder;
                    logs('gmo_epsilon')->addError('定期ID: '.$RegularOrder->getId().' エラーコード: '.$userInfoResults['err_code'].' エラーメッセージ: '.$userInfoResults['message']);
                    continue;
                }
                if ($userInfoResults['status'] === 'OK') {
                    if ($this->isExpireCreditCardService->handle($userInfoResults['cardExpire'])) {
                        $this->changeRegularStatus($RegularOrder, RegularStatus::PAYMENT_ERROR);
                        $PaymentErrorRegularOrders[] = $RegularOrder;
                        logs('gmo_epsilon')->addError('定期ID: '.$RegularOrder->getId().'について、クレジットカードの有効期限が切れているため、受注の作成・イプシロン決済サービス決済の登録は行っていません。');
                        continue;
                    }
                }
                $results = $this
                    ->requestCreateRegularOrderService
                    ->handle($RegularOrder, $Order, 'eccube_payment_lite4_regular_create_command');

                if ($results['status'] === 'NG') {
                    $this->changeRegularStatus($RegularOrder, RegularStatus::PAYMENT_ERROR);
                    $PaymentErrorRegularOrders[] = $RegularOrder;
                    logs('gmo_epsilon')->addError('定期ID: '.$RegularOrder->getId().' エラーコード: '.$results['err_code'].' エラーメッセージ: '.$results['message']);
                    continue;
                }
                $this->io->text('=== Send card3.cgi request for regular order id '.$RegularOrder->getId().'. ===');
                $card3cgiResult = $this->requestCard3Service->send($results['redirectUrl']);
                if (!$card3cgiResult) {
                    $this->changeRegularStatus($RegularOrder, RegularStatus::PAYMENT_ERROR);
                    $PaymentErrorRegularOrders[] = $RegularOrder;
                    logs('gmo_epsilon')->addError('定期ID: '.$RegularOrder->getId().'について、card3.cgiのリクエスト時に予期せぬエラーが発生しました。');
                    continue;
                }
                $getSalesResult = $this
                    ->requestGetSales2Service
                    ->handle(null, $results['order_no']);
                if ((int) $getSalesResult['state'] !== PaymentStatus::CHARGED && (int) $getSalesResult['state'] !== PaymentStatus::TEMPORARY_SALES) {
                    $this->changeRegularStatus($RegularOrder, RegularStatus::PAYMENT_ERROR);
                    $PaymentErrorRegularOrders[] = $RegularOrder;
                    logs('gmo_epsilon')->addError('定期ID: '.$RegularOrder->getId().'について、イプシロン決済サービスに有効な決済が登録されませんでした。 state = '.$getSalesResult['state']);
                    continue;
                }
            } elseif (!is_null($Order->getPayment()) && $Order->getPayment()->getMethodClass() === Cash::class) {
                $this->updateNormalPaymentOrderService->updateAfterMakingOrder($Order);
                $this->updateRegularOrderService->updateAfterMakingPayment($RegularOrder);
            } else {
                continue;
            }
            $SuccessRegularOrders[] = $RegularOrder;
        }

        // 受注作成バッチ結果メール送信
        $this
            ->orderCreationBatchResultMailService
            ->sendMail(
                $PaymentErrorRegularOrders,
                count($RegularOrders),
                count($SuccessRegularOrders),
                count($PaymentErrorRegularOrders),
                count($SystemErrorRegularOrders)
            );
        //指定定期回数の通知メール送信
        if (count($RegularOrderSpecifiedList) > 0) {
            $this->sendMailRegularSpecifiedCountNotification($RegularOrderSpecifiedList);
        }
        if (count($RegularOrders) === 0) {
            $this->io->text('=== RegularOrder Batch not found target. ===');
            logs('gmo_epsilon')->addInfo('=== RegularOrder Batch not found target. ===');
        }
        foreach ($SuccessRegularOrders as $regularOrder) {
            $this->io->text('=== Created an order for regular order id = '.$regularOrder->getId().'. ===');
            logs('gmo_epsilon')->addInfo('=== Created an order for regular order id = '.$regularOrder->getId().'. ===');
        }
        $this->io->text('=== RegularOrder Batch end. ===');
        logs('gmo_epsilon')->addInfo('=== RegularOrder Batch end. ===');
    }

    private function isExistProductClass(RegularOrder $RegularOrder): bool
    {
        /** @var RegularOrderItem[] $RegularProductOrderItems */
        $RegularProductOrderItems = $RegularOrder->getRegularProductOrderItems();
        $count = $this
            ->regularOrderRepository
            ->getNotAbolishedProductClass(
                $RegularProductOrderItems[0]->getProductClass()->getProduct()->getId()
            );
        if ($count === 0) {
            return false;
        }

        return true;
    }

    private function changeRegularStatus(RegularOrder $RegularOrder, int $regularStatusId): void
    {
        $RegularStatus = $this->updateRegularStatusService->handle($RegularOrder, $regularStatusId);
        $this->io->text('=== RegularOrderId = '.$RegularOrder->getId().' 定期ステータスを'.$RegularStatus->getName().'に変更しました ===');
        logs('gmo_epsilon')->addInfo('=== RegularOrderId = '.$RegularOrder->getId().' 定期ステータスを'.$RegularStatus->getName().'に変更しました ===');
    }

    private function checkRegularSuspendExpired(): void
    {
        /** @var RegularStatus $RegularStatusSuspend */
        $RegularStatusSuspend = $this->regularStatusRepository->find(RegularStatus::SUSPEND);
        /** @var RegularOrder[] $suspendRegularOrders */
        $suspendRegularOrders = $this->regularOrderRepository->findBy([
            'RegularStatus' => $RegularStatusSuspend,
        ]);
        foreach ($suspendRegularOrders as $suspendRegularOrder) {
            if (!$this->isResumeRegularOrder->handle($suspendRegularOrder)) {
                $this->changeRegularStatus($suspendRegularOrder, RegularStatus::CANCELLATION_EXPIRED_RESUMPTION);
            }
        }
    }

    private function sendMailRegularSpecifiedCountNotification($RegularOrderSpecifiedList)
    {
        foreach ($RegularOrderSpecifiedList as $RegularOrder) {
            $this
                ->regularSpecifiedCountNotificationMailService
                ->sendMail($RegularOrder);
        }
    }
}
