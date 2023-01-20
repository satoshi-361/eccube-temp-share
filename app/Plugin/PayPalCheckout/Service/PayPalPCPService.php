<?php

namespace Plugin\PayPalCheckout\Service;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Cart;
use Eccube\Entity\CartItem;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Exception;
use Plugin\PayPalCheckout\Entity\Config;
use Plugin\PayPalCheckout\Exception\PayPalCheckoutException;
use Plugin\PayPalCheckout\Exception\PayPalRequestException;
use Plugin\PayPalCheckout\Repository\ConfigRepository;
use stdClass;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PayPalPCPService
 * @package Plugin\PayPalCheckout\Service
 */
class PayPalPCPService
{
    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    /**
     * @var PayPalPCPRequestService
     */
    private $client;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * PayPalService constructor.
     * @param EccubeConfig $eccubeConfig
     * @param PayPalPCPRequestService $requestService
     * @param LoggerService $loggerService
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        PayPalPCPRequestService $requestService,
        LoggerService $loggerService,
        ConfigRepository $configRepository
    ) {
        $this->config = $configRepository->get();
        $this->eccubeConfig = $eccubeConfig;
        $this->client = $requestService;
        $clientId = $this->eccubeConfig->get('paypal.ci');//
        $clientSecret = $this->eccubeConfig->get('paypal.cs'); //
        $this->client->setEnv($clientId, $clientSecret, false);
        $this->logger = $loggerService;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->eccubeConfig->get('paypal.debug') ?? false;
    }

    /**
     * @param callable $afterProcessing
     * @throws PayPalRequestException
     * @throws PayPalCheckoutException
     */
    public function createPartnerReferralsRequest(callable $afterProcessing): void
    {
        /** @var stdClass $options */
        $options = new stdClass();
        $options->seller_nonce = hash("sha256", $this->eccubeConfig->get('paypal.seller_nonce') ?? "abc");

        $clientId = $this->eccubeConfig->get('paypal.ci');//
        $clientSecret = $this->eccubeConfig->get('paypal.cs'); //
        $this->client->setEnv($clientId, $clientSecret, false);

        $request = $this->client->preparePartnerReferrals($options);
        $this->logger->debug("CreatePartnerReferralsRequest contains the following parameters", $request->body);
        $response = $this->client->partnerReferrals($request);
        try {
            call_user_func($afterProcessing, $response);
        } catch (Exception $e) {
            throw new PayPalCheckoutException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param stdClass $options
     * @param callable $afterProcessing
     * @throws PayPalRequestException
     * @throws PayPalCheckoutException
     */
    public function createOauth2TokenRequest(stdClass $options, callable $afterProcessing): void
    {

        $client = new PayPalPCPRequestService();
        $client->setEnv($options->sharedId, '', false);

        $options->seller_nonce = hash("sha256", $this->eccubeConfig->get('paypal.seller_nonce') ?? "abc");

        $request = $client->prepareOauth2Token($options);
        $this->logger->debug("createOauth2TokenRequest contains the following parameters", ['body' => $request->body]);
        $response = $client->oauth2Token($request);
        try {
            call_user_func($afterProcessing, $response);
        } catch (Exception $e) {
            throw new PayPalCheckoutException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param stdClass $options
     * @param callable $afterProcessing
     * @throws PayPalRequestException
     * @throws PayPalCheckoutException
     */
    public function createMerchantCredentialRequest(stdClass $options, callable $afterProcessing): void
    {

        $partnerId = $this->eccubeConfig->get('paypal.partnerid');
        $options->partnerId = $partnerId;

        $clientId = $this->eccubeConfig->get('paypal.ci');//
        $clientSecret = $this->eccubeConfig->get('paypal.cs'); //
        $this->client->setEnv($clientId, $clientSecret, false);

        $request = $this->client->prepareMerchantCredential($options);
        $this->logger->debug("createMerchantCredentialRequest contains the following parameters", ['body' => $request->body]);
        $response = $this->client->merchantCredential($request);
        try {
            call_user_func($afterProcessing, $response);
        } catch (Exception $e) {
            throw new PayPalCheckoutException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
