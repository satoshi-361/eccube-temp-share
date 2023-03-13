<?php

namespace Customize\Service;

use Eccube\Common\EccubeConfig;
use Eccube\Util\CacheUtil;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Repository\CustomerRepository;
use Eccube\Service\MailService;
use Eccube\Entity\Master\CustomerStatus;
use Customize\Entity\TransferHistory;
use Plugin\PayPalCheckout\Repository\ConfigRepository as PaypalConfigRepository;
use PayPal\Api\Payout;
use PayPal\Api\PayoutSenderBatchHeader;
use PayPal\Api\PayoutItem;

class PaypalTransferHelper
{
    /**
     * @var eccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var PaypalConfig
     */
    private $paypalConfig;

    /**
     * @var MailService
     */
    private $mailService;

    /**
     * @var CacheUtil
     */
    protected $cacheUtil;

    public function __construct(
        EccubeConfig $eccubeConfig,
        EntityManagerInterface $entityManager,
        CustomerRepository $customerRepository,
        PaypalConfigRepository $paypalConfigRepository,
        MailService $mailService,
        CacheUtil $cacheUtil
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->entityManager = $entityManager;
        $this->customerRepository = $customerRepository;
        $this->mailService = $mailService;
        $this->cacheUtil = $cacheUtil;
        
        $this->paypalConfig = $paypalConfigRepository->get();
    }

    /**
     * 毎月10日に送金するため、今月1～10日の報酬額は除いて送金する必要があります。
     */
    public function startPaypalPayout() {
        $transferHistoryRepository = $this->entityManager->getRepository(TransferHistory::class);
        $ActiveCustomerStatus = $this->entityManager->getRepository(CustomerStatus::class)->find(2);

        $clientId = $this->paypalConfig->getClientId();
        $clientSecret = $this->paypalConfig->getClientSecret();

        $apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential( $clientId, $clientSecret )
        );

        $payouts0 = new Payout();

        $senderBatchHeader = new PayoutSenderBatchHeader();
        $senderBatchHeader->setSenderBatchId(uniqid())
            ->setEmailSubject('ペイパルで振り込みます。');
        $payouts0->setSenderBatchHeader($senderBatchHeader);

        $Customers = $this->customerRepository->findBy(['Status' => $ActiveCustomerStatus]);
        foreach ( $Customers as $Customer ) {
            // 今月の報酬額を集計して送金額から除外します。
            $startDate = new \DateTime(date('Y-m-01 00:00:00'));
            $endDate = new \DateTime(date('Y-m-09 23:59:59'));
            $aggregatedMoney = $transferHistoryRepository->aggregateMoneyByPeriod( $Customer, $startDate, $endDate );

            $transferableMoney = $Customer->getBalance() - $aggregatedMoney;
            if ( $transferableMoney == 0 ) continue;
            
            if ( !$Customer->getPaypalEmail() ) {
                $this->mailService->sendPaypalMailNotRegistered( $Customer );

                continue;
            }

            $senderItem = new PayoutItem();
            $senderItem->setRecipientType('Email')
                ->setNote('ありがとうございます。')
                ->setReceiver($Customer->getPaypalEmail())
                ->setSenderItemId('customer' . uniqid())
                ->setAmount(new \PayPal\Api\Currency('{
                    "value":"' . $transferableMoney .'",
                    "currency":"JPY"
                }'));

            $payouts = clone $payouts0;
            $payouts->addItem($senderItem);
            
            $request = clone $payouts;
            $paypalTransferHistory = new TransferHistory();
            $paypalTransferHistory->setCustomerId($Customer->getId());
            $paypalTransferHistory->setTransferable($transferableMoney);
            
            try {
                $output = $payouts->createSynchronous($apiContext);
            } catch (\PayPal\Exception\PayPalConnectionException $ex) {
                $paypalTransferHistory->setTransfered(0);
                $paypalTransferHistory->setBalance($transferableMoney);
                $paypalTransferHistory->setTransferedStatus(false);
                $paypalTransferHistory->setTransferedDate( new \DateTime() );
                $paypalTransferHistory->setMessage($ex->getCode());

                $this->mailService->sendTransferFailedMail( $Customer, [
                    'month' =>  date('m', strtotime( $paypalTransferHistory->getTransferedDate()->format('Y-m-d') . '-1 month' )),
                    'transfer_money' => $paypalTransferHistory->getTransfered()
                ]);

                $this->entityManager->persist($paypalTransferHistory);
                continue;
            }

            $paypalTransferHistory->setTransfered($transferableMoney);
            $paypalTransferHistory->setBalance(0);
            $paypalTransferHistory->setTransferedDate( new \DateTime() );
            $paypalTransferHistory->setTransferedStatus(true);

            $this->mailService->sendTransferSuccessMail( $Customer, [
                'month' =>  date('m', strtotime( $paypalTransferHistory->getTransferedDate()->format('Y-m-d') . '-1 month' )),
                'transfer_money' => $paypalTransferHistory->getTransfered()
            ]);

            $Customer->setBalance($aggregatedMoney);
            $this->entityManager->persist($Customer);
            $this->entityManager->persist($paypalTransferHistory);
        }

        $this->entityManager->flush();
        $this->cacheUtil->clearDoctrineCache();
    }
    
    /**
     * 毎月15日に送金するため、今月1～15日の報酬額は除いて送金する必要があります。
     */
    public function startPaypalPayout15() {
        $transferHistoryRepository = $this->entityManager->getRepository(TransferHistory::class);
        $ActiveCustomerStatus = $this->entityManager->getRepository(CustomerStatus::class)->find(2);

        $clientId = $this->paypalConfig->getClientId();
        $clientSecret = $this->paypalConfig->getClientSecret();

        $apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential( $clientId, $clientSecret )
        );

        $payouts0 = new Payout();

        $senderBatchHeader = new PayoutSenderBatchHeader();
        $senderBatchHeader->setSenderBatchId(uniqid())
            ->setEmailSubject('ペイパルで振り込みます。');
        $payouts0->setSenderBatchHeader($senderBatchHeader);

        $Customers = $this->customerRepository->findBy(['Status' => $ActiveCustomerStatus]);
        foreach ( $Customers as $Customer ) {
            // 今月の報酬額を集計して送金額から除外します。
            $startDate = new \DateTime(date('Y-m-01 00:00:00'));
            $endDate = new \DateTime(date('Y-m-15 23:59:59'));
            $aggregatedMoney = $transferHistoryRepository->aggregateMoneyByPeriod( $Customer, $startDate, $endDate );

            $transferableMoney = $Customer->getBalance() - $aggregatedMoney;
            if ( $transferableMoney == 0 ) continue;
            
            if ( !$Customer->getPaypalEmail() ) {
                $this->mailService->sendPaypalMailNotRegistered( $Customer );

                continue;
            }

            $senderItem = new PayoutItem();
            $senderItem->setRecipientType('Email')
                ->setNote('ありがとうございます。')
                ->setReceiver($Customer->getPaypalEmail())
                ->setSenderItemId('customer' . uniqid())
                ->setAmount(new \PayPal\Api\Currency('{
                    "value":"' . $transferableMoney .'",
                    "currency":"JPY"
                }'));

            $payouts = clone $payouts0;
            $payouts->addItem($senderItem);
            
            $request = clone $payouts;
            $paypalTransferHistory = new TransferHistory();
            $paypalTransferHistory->setCustomerId($Customer->getId());
            $paypalTransferHistory->setTransferable($transferableMoney);
            
            try {
                $output = $payouts->createSynchronous($apiContext);
            } catch (\PayPal\Exception\PayPalConnectionException $ex) {
                $paypalTransferHistory->setTransfered(0);
                $paypalTransferHistory->setBalance($transferableMoney);
                $paypalTransferHistory->setTransferedStatus(false);
                $paypalTransferHistory->setTransferedDate( new \DateTime() );
                $paypalTransferHistory->setMessage($ex->getCode());

                $this->mailService->sendTransferFailedMail( $Customer, [
                    'month' =>  date('m', strtotime( $paypalTransferHistory->getTransferedDate()->format('Y-m-d') . '-1 month' )),
                    'transfer_money' => $paypalTransferHistory->getTransfered()
                ]);

                $this->entityManager->persist($paypalTransferHistory);
                continue;
            }

            $paypalTransferHistory->setTransfered($transferableMoney);
            $paypalTransferHistory->setBalance(0);
            $paypalTransferHistory->setTransferedDate( new \DateTime() );
            $paypalTransferHistory->setTransferedStatus(true);

            $this->mailService->sendTransferSuccessMail( $Customer, [
                'month' =>  date('m', strtotime( $paypalTransferHistory->getTransferedDate()->format('Y-m-d') . '-1 month' )),
                'transfer_money' => $paypalTransferHistory->getTransfered()
            ]);

            $Customer->setBalance($aggregatedMoney);
            $this->entityManager->persist($Customer);
            $this->entityManager->persist($paypalTransferHistory);
        }

        $this->entityManager->flush();
        $this->cacheUtil->clearDoctrineCache();
    }
    
    /**
     * 毎月30日に送金するため、今月16～30日の報酬額は除いて送金する必要があります。
     */
    public function startPaypalPayout30() {
        $transferHistoryRepository = $this->entityManager->getRepository(TransferHistory::class);
        $ActiveCustomerStatus = $this->entityManager->getRepository(CustomerStatus::class)->find(2);

        $clientId = $this->paypalConfig->getClientId();
        $clientSecret = $this->paypalConfig->getClientSecret();

        $apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential( $clientId, $clientSecret )
        );

        $payouts0 = new Payout();

        $senderBatchHeader = new PayoutSenderBatchHeader();
        $senderBatchHeader->setSenderBatchId(uniqid())
            ->setEmailSubject('ペイパルで振り込みます。');
        $payouts0->setSenderBatchHeader($senderBatchHeader);

        $Customers = $this->customerRepository->findBy(['Status' => $ActiveCustomerStatus]);
        foreach ( $Customers as $Customer ) {
            // 今月の報酬額を集計して送金額から除外します。
            $startDate = new \DateTime(date('Y-m-16 00:00:00'));
            $endDate = new \DateTime(date('Y-m-31 23:59:59'));
            $aggregatedMoney = $transferHistoryRepository->aggregateMoneyByPeriod( $Customer, $startDate, $endDate );

            $transferableMoney = $Customer->getBalance() - $aggregatedMoney;
            if ( $transferableMoney == 0 ) continue;
            
            if ( !$Customer->getPaypalEmail() ) {
                $this->mailService->sendPaypalMailNotRegistered( $Customer );

                continue;
            }

            $senderItem = new PayoutItem();
            $senderItem->setRecipientType('Email')
                ->setNote('ありがとうございます。')
                ->setReceiver($Customer->getPaypalEmail())
                ->setSenderItemId('customer' . uniqid())
                ->setAmount(new \PayPal\Api\Currency('{
                    "value":"' . $transferableMoney .'",
                    "currency":"JPY"
                }'));

            $payouts = clone $payouts0;
            $payouts->addItem($senderItem);
            
            $request = clone $payouts;
            $paypalTransferHistory = new TransferHistory();
            $paypalTransferHistory->setCustomerId($Customer->getId());
            $paypalTransferHistory->setTransferable($transferableMoney);
            
            try {
                $output = $payouts->createSynchronous($apiContext);
            } catch (\PayPal\Exception\PayPalConnectionException $ex) {
                $paypalTransferHistory->setTransfered(0);
                $paypalTransferHistory->setBalance($transferableMoney);
                $paypalTransferHistory->setTransferedStatus(false);
                $paypalTransferHistory->setTransferedDate( new \DateTime() );
                $paypalTransferHistory->setMessage($ex->getCode());

                $this->mailService->sendTransferFailedMail( $Customer, [
                    'month' =>  date('m', strtotime( $paypalTransferHistory->getTransferedDate()->format('Y-m-d') . '-1 month' )),
                    'transfer_money' => $paypalTransferHistory->getTransfered()
                ]);

                $this->entityManager->persist($paypalTransferHistory);
                continue;
            }

            $paypalTransferHistory->setTransfered($transferableMoney);
            $paypalTransferHistory->setBalance(0);
            $paypalTransferHistory->setTransferedDate( new \DateTime() );
            $paypalTransferHistory->setTransferedStatus(true);

            $this->mailService->sendTransferSuccessMail( $Customer, [
                'month' =>  date('m', strtotime( $paypalTransferHistory->getTransferedDate()->format('Y-m-d') . '-1 month' )),
                'transfer_money' => $paypalTransferHistory->getTransfered()
            ]);

            $Customer->setBalance($aggregatedMoney);
            $this->entityManager->persist($Customer);
            $this->entityManager->persist($paypalTransferHistory);
        }

        $this->entityManager->flush();
        $this->cacheUtil->clearDoctrineCache();
    }
    
    // public function startPaypalPayout1() {
    //     $this->mailService->sendCronTestMail();
    // }
}
