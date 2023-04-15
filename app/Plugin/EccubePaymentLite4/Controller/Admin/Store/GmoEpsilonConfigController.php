<?php

namespace Plugin\EccubePaymentLite4\Controller\Admin\Store;

use Doctrine\Common\Collections\ArrayCollection;
use Eccube\Common\EccubeConfig;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Payment;
use Eccube\Repository\Master\SaleTypeRepository;
use Eccube\Repository\PaymentRepository;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\GmoEpsilonPayment;
use Plugin\EccubePaymentLite4\Form\Type\Admin\ConfigType;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequestService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonUrlService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class GmoEpsilonConfigController extends AbstractController
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var GmoEpsilonRequestService
     */
    protected $gmoEpsilonRequestService;

    /**
     * @var SaleTypeRepository
     */
    protected $saleTypeRepository;
    /**
     * @var GmoEpsilonUrlService
     */
    private $gmoEpsilonUrlService;

    public function __construct(
        EccubeConfig $eccubeConfig,
        ConfigRepository $configRepository,
        PaymentRepository $paymentRepository,
        GmoEpsilonRequestService $gmoEpsilonRequestService,
        SaleTypeRepository $saleTypeRepository,
        GmoEpsilonUrlService $gmoEpsilonUrlService
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->configRepository = $configRepository;
        $this->paymentRepository = $paymentRepository;
        $this->gmoEpsilonRequestService = $gmoEpsilonRequestService;
        $this->saleTypeRepository = $saleTypeRepository;
        $this->gmoEpsilonUrlService = $gmoEpsilonUrlService;
    }

    /**
     * @Route(
     *     "/%eccube_admin_route%/eccube_payment_lite/store/plugin/config",
     *     name="eccube_payment_lite4_admin_config"
     * )
     * @Template("@EccubePaymentLite4/admin/config.twig")
     */
    public function index(Request $request)
    {
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        $originalIpBlackList = new ArrayCollection();
        foreach ($Config->getIpBlackList() as $list) {
            $originalIpBlackList->add($list);
        }
        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);

        // ssl対応判定
        if (!extension_loaded('openssl')) {
            $form['environmental_setting']->addError(new FormError('※ このサーバはSSLに対応していません。'));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Config $Config */
            $Config = $form->getData();
            $st_code = '';
            $paymentIds = $Config->getGmoEpsilonPayments()->map(function ($GmoEpsilonPayment) {
                return $GmoEpsilonPayment->getId();
            })->toArray();
            $st_code .= in_array($this->eccubeConfig['gmo_epsilon']['pay_id']['credit'], $paymentIds) ? '1' : '0';
            $st_code .= '0'; // credit 2
            $st_code .= in_array($this->eccubeConfig['gmo_epsilon']['pay_id']['conveni'], $paymentIds) ? '1' : '0';
            $st_code .= in_array($this->eccubeConfig['gmo_epsilon']['pay_id']['netbank_jnb'], $paymentIds) ? '1' : '0';
            $st_code .= in_array($this->eccubeConfig['gmo_epsilon']['pay_id']['netbank_rakuten'], $paymentIds) ? '1' : '0';
            $st_code .= '-0'; // "-" unused 6
            $st_code .= in_array($this->eccubeConfig['gmo_epsilon']['pay_id']['payeasy'], $paymentIds) ? '1' : '0';
            $st_code .= in_array($this->eccubeConfig['gmo_epsilon']['pay_id']['webmoney'], $paymentIds) ? '1' : '0';
            $st_code .= in_array($this->eccubeConfig['gmo_epsilon']['pay_id']['ywallet'], $paymentIds) ? '1' : '0';
            $st_code .= '-0'; // "-" unused 10
            $st_code .= in_array($this->eccubeConfig['gmo_epsilon']['pay_id']['paypal'], $paymentIds) ? '1' : '0';
            $st_code .= in_array($this->eccubeConfig['gmo_epsilon']['pay_id']['bitcash'], $paymentIds) ? '1' : '0';
            $st_code .= in_array($this->eccubeConfig['gmo_epsilon']['pay_id']['chocom'], $paymentIds) ? '1' : '0';
            $st_code .= '0-'; // unused 14
            $st_code .= in_array($this->eccubeConfig['gmo_epsilon']['pay_id']['sphone'], $paymentIds) ? '1' : '0';
            $st_code .= in_array($this->eccubeConfig['gmo_epsilon']['pay_id']['jcb'], $paymentIds) ? '1' : '0';
            $st_code .= in_array($this->eccubeConfig['gmo_epsilon']['pay_id']['sumishin'], $paymentIds) ? '1' : '0';
            $st_code .= in_array($this->eccubeConfig['gmo_epsilon']['pay_id']['deferred'], $paymentIds) ? '1' : '0';
            $st_code .= '0-00';
            $st_code .= in_array($this->eccubeConfig['gmo_epsilon']['pay_id']['virtual_account'], $paymentIds) ? '1' : '0';
            $st_code .= '00-';
            $st_code .= in_array($this->eccubeConfig['gmo_epsilon']['pay_id']['paypay'], $paymentIds) ? '1' : '0';
            $st_code .= '0000-00000';

            $arrParameter = [
                'contract_code' => $Config->getContractCode(),
                'user_id' => 'connect_test',
                'user_name' => '接続テスト',
                'user_mail_add' => 'test@test.co.jp',
                'st_code' => $st_code,
                'process_code' => '3',
                'xml' => '1',
            ];

            $arrXML = $this->gmoEpsilonRequestService->sendData(
                $this->gmoEpsilonUrlService->getUrl('receive_order3'),
                $arrParameter
            );
            $err_code = $this->gmoEpsilonRequestService->getXMLValue($arrXML, 'RESULT', 'ERR_CODE');
            switch ($err_code) {
                case '':
                    break;
                case '607':
                    $form['contract_code']->addError(new FormError('※ 契約コードが違います。'));
                    break;
                default:
                    $form['contract_code']->addError(new FormError('※ '.$this->gmoEpsilonRequestService->getXMLValue($arrXML, 'RESULT', 'ERR_DETAIL')));
                    break;
            }

            if ($form->isValid()) {
                // 決済方法を追加
                $this->savePaymentData($Config->getGmoEpsilonPayments());
                $this->entityManager->persist($Config);
                $this->entityManager->flush();

                $this->addSuccess('gmo_epsilon.admin.save.success', 'admin');

                return $this->redirectToRoute('eccube_payment_lite4_admin_config');
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    public function savePaymentData($GmoEpsilonPayments)
    {
        $Payment = $this->paymentRepository->findOneBy([], ['sort_no' => 'DESC']);
        $sortNo = $Payment ? $Payment->getSortNo() + 1 : 1;
        foreach ($GmoEpsilonPayments as $GmoEpsilonPayment) {
            /** @var GmoEpsilonPayment $GmoEpsilonPayment */
            /** @var Payment $Payment */
            $Payment = $this->paymentRepository->findOneBy(['method_class' => $GmoEpsilonPayment->getMethodClass()]);
            if (is_null($Payment)) {
                $Payment = new Payment();
                $Payment->setCharge($GmoEpsilonPayment->getCharge());
                $Payment->setSortNo($sortNo);
                $Payment->setVisible(true);
                $Payment->setMethod($GmoEpsilonPayment->getName());
                $Payment->setMethodClass($GmoEpsilonPayment->getMethodClass());
                $Payment->setRuleMin($GmoEpsilonPayment->getRuleMin());
                $Payment->setRuleMax($GmoEpsilonPayment->getRuleMax());
                $Payment->setVisible(true);
            } else {
                $Payment->setVisible(true);
            }
            $this->entityManager->persist($Payment);
            $sortNo++;
        }
    }

    /**
     * Button update payment table
     *
     * @Route(
     *     "/%eccube_admin_route%/eccube_payment_lite/store/plugin/update_payment_table",
     *     name="eccube_payment_lite4_admin_update_payment_table"
     * )
     */
    public function updatePaymentData()
    {
        $Payments = $this->paymentRepository->findBy([], ['sort_no' => 'ASC']);

        $gmoEpsilon4 = $this->eccubeConfig['gmo_epsilon']['payment']['gmo_epsilon4'];
        $ecPaymentLite4 = $this->eccubeConfig['gmo_epsilon']['payment']['ec_payment_lite4'];

        $arrPaymentGmo = [];
        $arrPaymentLite4 = [];

        if (isset($Payments)) {
            foreach ($Payments as $payment) {
                if (strpos($payment->getMethodClass(), $gmoEpsilon4)) {
                    $arrPaymentGmo[] = $payment;
                } elseif (strpos($payment->getMethodClass(), $ecPaymentLite4)) {
                    $arrPaymentLite4[] = $payment;
                }
            }

            try {
                foreach ($arrPaymentGmo as $paymentGmo) {
                    $method_class = str_replace($gmoEpsilon4, $ecPaymentLite4, $paymentGmo->getMethodClass());
                    $paymentGmo->setMethodClass($method_class);
                    $this->entityManager->persist($paymentGmo);
                    $this->entityManager->flush();
                    foreach ($arrPaymentLite4 as $paymentLite4) {
                        if ($paymentGmo->getMethod() === $paymentLite4->getMethod()) {
                            $this->paymentRepository->delete($paymentLite4);
                            $this->entityManager->flush();
                        }
                    }
                }
                $this->addSuccess('gmo_epsilon.admin.save.update_success', 'admin');
            } catch (\Exception $exception) {
                log_info('Update Payment Data Error: '.$exception->getMessage());
                $this->addError('gmo_epsilon.admin.save.failed', 'admin');
            }
        } else {
            $this->addError('gmo_epsilon.admin.save.failed', 'admin');
        }

        return $this->redirectToRoute('eccube_payment_lite4_admin_config');
    }
}
