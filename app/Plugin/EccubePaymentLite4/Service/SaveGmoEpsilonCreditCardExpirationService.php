<?php

namespace Plugin\EccubePaymentLite4\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Customer;
use Eccube\Repository\CustomerRepository;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestGetUserInfoService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SaveGmoEpsilonCreditCardExpirationService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var RequestGetUserInfoService
     */
    private $requestGetUserInfoService;
    /**
     * @var CustomerRepository
     */
    private $customerRepository;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var GetCardExpireDateTimeService
     */
    private $getCardExpireDateTimeService;

    public function __construct(
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        RequestGetUserInfoService $requestGetUserInfoService,
        CustomerRepository $customerRepository,
        ConfigRepository $configRepository,
        GetCardExpireDateTimeService $getCardExpireDateTimeService
    ) {
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->requestGetUserInfoService = $requestGetUserInfoService;
        $this->customerRepository = $customerRepository;
        $this->configRepository = $configRepository;
        $this->getCardExpireDateTimeService = $getCardExpireDateTimeService;
    }

    public function handle()
    {
        // ゲスト購入の場合は処理を行わない
        if (is_null($this->session->get('_security_customer'))) {
            return;
        }
        /** @var Customer $customer */
        $customer = unserialize($this->session->get('_security_customer'))->getUser();
        /** @var Customer $Customer */
        $Customer = $this->customerRepository->find($customer->getId());
        $results = $this->requestGetUserInfoService->handle($Customer->getId());
        // クレジットカード有効期限を保存する
        if ($results['status'] === 'OK' && strpos($results['cardExpire'], '/') !== false) {
            $Customer->setGmoEpsilonCreditCardExpirationDate($this->getCardExpireDateTimeService->get($results['cardExpire']));
        }
        $Customer->setCardChangeRequestMailSendDate(null);
        $this->entityManager->persist($Customer);
        $this->entityManager->flush();
    }
}
