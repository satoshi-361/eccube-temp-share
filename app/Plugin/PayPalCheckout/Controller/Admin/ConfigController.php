<?php

namespace Plugin\PayPalCheckout\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\PayPalCheckout\Entity\Config;
use Plugin\PayPalCheckout\Entity\PluginSetting;
use Plugin\PayPalCheckout\Service\PayPalPCPService;
use Plugin\PayPalCheckout\Exception\PayPalCheckoutException;
use Plugin\PayPalCheckout\Form\Type\Admin\ConfigType;
use Plugin\PayPalCheckout\Repository\ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use stdClass;

use Plugin\PayPalCheckout\Service\LoggerService;

/**
 * Class ConfigController
 * @package Plugin\PayPalCheckout\Controller\Admin
 */
class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var PayPalPCPService
     */
    protected $pcpPaypal;

    /**
     * @var Logger
     */
    protected $logger;


    /**
     * ConfigController constructor.
     * @param ConfigRepository $configRepository
     */
    public function __construct(ConfigRepository $configRepository,
                                PayPalPCPService $pcpPaypal,
                                LoggerService $loggerService
    ) {
        $this->configRepository = $configRepository;
        $this->pcpPaypal = $pcpPaypal;
        $this->logger = $loggerService;
    }

    /**
     * @Method("GET")
     * @Route("/%eccube_admin_route%/paypal/config", name="pay_pal_checkout_admin_config")
     * @Template("@PayPalCheckout/admin/config.twig")
     *
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        try {
            $this->pcpPaypal->createPartnerReferralsRequest(function ($response) use (&$actionUrl) {
                $actionUrl = $response->getActionUrl();
        });
        } catch (PayPalCheckoutException $e) {
            $this->logger->error('ISU has been failed', array_merge([
                'headers' => $request->headers->all(),
                'request' => $request->request->all(),
                'statusCode' => $e->getCode(),
                'message' => $e->getMessage(),
            ], $this->pcpPaypal->isDebug() ? [
                'trace' => $e->getTrace()
            ] : []));
//            throw new BadRequestHttpException("ISU has been failed", $e, $e->getCode());
            $actionUrl = '';
        }
        $this->logger->debug("actionUrl". $actionUrl);

        /** @var Config $config */
        $config = $this->configRepository->get();

        /** @var FormBuilder $builder */
        $builder = $this->formFactory->createBuilder(ConfigType::class, $config->createPluginSettingForm());

        /* @var Form $form */
        $form = $builder->getForm();

        return [
            'form' => $form->createView(),
            "actionUrl" => $actionUrl
        ];
    }

    /**
     * @Method("POST")
     * @Route("/%eccube_admin_route%/paypal/config", name="pay_pal_checkout_admin_config_submit")
     *
     * @param Request $request
     * @return array|RedirectResponse
     * @throws PayPalCheckoutException
     */
    public function post(Request $request)
    {
        /* @var Form $form */
        $form = $this->createForm(ConfigType::class);
        $form->handleRequest($request);

        switch (true) {
            case $form->isSubmitted() && $form->isValid():
                /** @var PluginSetting $PluginSetting */
                $PluginSetting = $form->getData();
                /** @var Config $Config */
                $Config = $this->configRepository->get();
                $Config->savePluginSetting($PluginSetting);
                $this->entityManager->persist($Config);
                $this->entityManager->flush();
                $this->addSuccess('登録しました。', 'admin');
                break;
            case $form->isSubmitted() && !$form->isValid():
                $this->addError('失敗しました。', 'admin');
                return [
                    'form' => $form->createView(),
                ];
            default:
                throw new PayPalCheckoutException();
        }

        return $this->redirectToRoute('pay_pal_checkout_admin_config');
    }

    /**
     * @Method("POST")
     * @Route("/%eccube_admin_route%/paypal/config/login", name="pay_pal_checkout_admin_login")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request)
    {
//        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
//            return $this->json([], Response::HTTP_UNAUTHORIZED);
//        }

        $json_content = $request->getContent();

        $this->logger->debug('ISU logged in', [
            'headers' => $request->headers->all(),
            'request' => $request->request->all(),
            'json' => $json_content
        ]);

        $sharedId = json_decode($json_content)->sharedId;
        $authCode = json_decode($json_content)->authCode;

        if(!isset($sharedId) || !isset($authCode)) {
            return $this->json([], Response::HTTP_UNAUTHORIZED);
        }

        try {
            /** @var stdClass $options */
            $oauth2Tokenoptions = new stdClass();
            $oauth2Tokenoptions->sharedId = $sharedId;
            $oauth2Tokenoptions->authCode = $authCode;
            $oauth2Tokenoptions->seller_nonce = '';

            $this->pcpPaypal->createOauth2TokenRequest($oauth2Tokenoptions, function ($response) use (&$access_token) {
                $access_token = $response->getAccessToken();
            });
            $this->logger->debug("access_token". $access_token);

            $merchantCredentialoptions = new stdClass();
            $merchantCredentialoptions->partnerId = '';
            $merchantCredentialoptions->access_token = $access_token;

            $this->pcpPaypal->createMerchantCredentialRequest($merchantCredentialoptions, function ($response) use (&$merchantClientId, &$merchantClientSecret) {
                $merchantClientId = $response->getClientId();
                $merchantClientSecret = $response->getClientSecret();
            });
            $this->logger->debug("merchant_clientID". $merchantClientId);
            $this->logger->debug("merchant_clientSecret". $merchantClientSecret);

            $responseData =  [
                "result" => [
                    'ClientId' => $merchantClientId,
                    'ClientSecret' => $merchantClientSecret
                ]
            ];
            return $this->json($responseData, Response::HTTP_OK);

        } catch (PayPalCheckoutException $e) {
            $this->logger->error('ISU has been failed', array_merge([
                'headers' => $request->headers->all(),
                'request' => $request->request->all(),
                'statusCode' => $e->getCode(),
                'message' => $e->getMessage(),
            ], $this->pcpPaypal->isDebug() ? [
                'trace' => $e->getTrace()
            ] : []));
            throw new BadRequestHttpException("ISU has been failed", $e, $e->getCode());
        }

    }
}
