<?php

namespace Plugin\EccubePaymentLite4\Controller\Front\Mypage;

use Eccube\Common\EccubeConfig;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Customer;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Service\ChangeRegularStatusToRePaymentService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestGetUserInfoService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestReceiveOrderService;
use Plugin\EccubePaymentLite4\Service\SaveGmoEpsilonCreditCardExpirationService;
use Plugin\EccubePaymentLite4\Service\UpdateNextShippingDateFromRePaymentService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class EditCreditCardController extends AbstractController
{
    /**
     * @var RequestGetUserInfoService
     */
    private $requestGetUserInfoService;
    /**
     * @var RequestReceiveOrderService
     */
    private $requestReceiveOrderService;
    /**
     * @var SaveGmoEpsilonCreditCardExpirationService
     */
    private $saveGmoEpsilonCreditCardExpirationService;
    /**
     * @var ChangeRegularStatusToRePaymentService
     */
    private $changeRegularStatusToRePaymentService;
    /**
     * @var UpdateNextShippingDateFromRePaymentService
     */
    private $updateNextShippingDateFromRePaymentService;
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    public function __construct(
        RequestGetUserInfoService $requestGetUserInfoService,
        RequestReceiveOrderService $requestReceiveOrderService,
        ChangeRegularStatusToRePaymentService $changeRegularStatusToRePaymentService,
        SaveGmoEpsilonCreditCardExpirationService $saveGmoEpsilonCreditCardExpirationService,
        UpdateNextShippingDateFromRePaymentService $updateNextShippingDateFromRePaymentService,
        ConfigRepository $configRepository,
        EccubeConfig $eccubeConfig
    ) {
        $this->requestGetUserInfoService = $requestGetUserInfoService;
        $this->requestReceiveOrderService = $requestReceiveOrderService;
        $this->changeRegularStatusToRePaymentService = $changeRegularStatusToRePaymentService;
        $this->saveGmoEpsilonCreditCardExpirationService = $saveGmoEpsilonCreditCardExpirationService;
        $this->updateNextShippingDateFromRePaymentService = $updateNextShippingDateFromRePaymentService;
        $this->configRepository = $configRepository;
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * @Route(
     *     "/mypage/eccube_payment_lite/credit_card",
     *     name="eccube_payment_lite4_mypage_credit_card_index"
     * )
     * @Template("@EccubePaymentLite4/default/Mypage/edit_credit_card.twig")
     *
     * @return array
     */
    public function index()
    {
        // Check active クレジットカード決済, 登録済みのクレジットカードで決済
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        $paymentIds = $Config->getGmoEpsilonPayments()->map(function ($GmoEpsilonPayment) {
            return $GmoEpsilonPayment->getId();
        })->toArray();

        if (count($paymentIds) > 0) {
            if (!in_array($this->eccubeConfig['gmo_epsilon']['pay_id']['credit'], $paymentIds) ||
                !in_array($this->eccubeConfig['gmo_epsilon']['pay_id']['reg_credit'], $paymentIds)) {
                throw new NotFoundHttpException();
            }
        }
        /** @var Customer $Customer */
        $Customer = unserialize($this->session->get('_security_customer'))->getUser();
        $results = $this->requestGetUserInfoService->handle($Customer->getId());
        $isRegisteredCreditCard = true;
        if ($results['status'] === 'NG') {
            $isRegisteredCreditCard = false;
        }

        return [
            'isRegisteredCreditCard' => $isRegisteredCreditCard,
            'cardBrand' => $results['cardBrand'],
            'cardExpire' => $results['cardExpire'],
            'cardNumberMask' => $results['cardNumberMask'],
        ];
    }

    /**
     * @Route(
     *     "/mypage/eccube_payment_lite/credit_card/create",
     *     name="eccube_payment_lite4_mypage_credit_card_new"
     * )
     * @Template("@EccubePaymentLite4/default/Mypage/edit_credit_card.twig")
     *
     * @return RedirectResponse
     */
    public function create()
    {
        if (is_null($this->session->get('_security_customer'))) {
            return $this->redirectToRoute('mypage_login');
        }
        /** @var Customer $Customer */
        $Customer = $this->getUser();
        $results = $this
            ->requestReceiveOrderService
            ->handle($Customer, 3, 'eccube_payment_lite4_mypage_credit_card_new');

        if ($results['status'] === 'NG') {
            return $this->redirectToRoute('eccube_payment_lite4_mypage_credit_card_index');
        }

        return $this->redirect($results['url']);
    }

    /**
     * @Route(
     *     "/mypage/eccube_payment_lite/credit_card/edit",
     *     name="eccube_payment_lite4_mypage_credit_card_edit"
     * )
     *
     * @return RedirectResponse
     */
    public function edit()
    {
        if (is_null($this->session->get('_security_customer'))) {
            return $this->redirectToRoute('mypage_login');
        }
        /** @var Customer $Customer */
        $Customer = $this->getUser();
        $results = $this
            ->requestReceiveOrderService
            ->handle($Customer, 4, 'eccube_payment_lite4_mypage_credit_card_edit');
        if ($results['status'] === 'NG') {
            return $this->redirectToRoute('eccube_payment_lite4_mypage_credit_card_index');
        }

        return $this->redirect($results['url']);
    }

    /**
     * @Route(
     *     "/mypage/eccube_payment_lite/credit_card/complete",
     *     name="eccube_payment_lite4_mypage_credit_card_complete"
     * )
     *
     * @return RedirectResponse
     */
    public function complete()
    {
        if (!$this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('eccube_payment_lite4_mypage_credit_card_index');
        }
        /** @var Customer $Customer */
        $Customer = $this->getUser();
        $this->saveGmoEpsilonCreditCardExpirationService->handle();
        $this->changeRegularStatusToRePaymentService->handle($Customer);
        $this->updateNextShippingDateFromRePaymentService->update($Customer);

        return $this->redirectToRoute('eccube_payment_lite4_mypage_credit_card_index');
    }
}
