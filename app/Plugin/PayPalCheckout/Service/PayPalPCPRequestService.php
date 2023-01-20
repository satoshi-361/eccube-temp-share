<?php

namespace Plugin\PayPalCheckout\Service;

use Plugin\PayPalCheckout\Lib\PayPalHttp\HttpException as PayPalHttpException;
use Plugin\PayPalCheckout\Lib\PayPalHttp\HttpRequest as PayPalHttpRequest;
use Plugin\PayPalCheckout\Lib\PayPalHttp\HttpResponse as PayPalHttpResponse;
use Plugin\PayPalCheckout\Lib\PayPalCheckoutSdk\Core\PayPalHttpClient;
use Plugin\PayPalCheckout\Lib\PayPalCheckoutSdk\Core\ProductionEnvironment;
use Plugin\PayPalCheckout\Lib\PayPalCheckoutSdk\Core\SandboxEnvironment;
use Plugin\PayPalCheckout\Contracts\PartnerReferralsResponse;
use Plugin\PayPalCheckout\Contracts\Oauth2TokenResponse;
use Plugin\PayPalCheckout\Contracts\MerchantCredentialResponse;
use Plugin\PayPalCheckout\Exception\PayPalPCPRequestException;
use stdClass;

/**
 * Class PayPalPCPRequestService
 * @package Plugin\PayPalCheckout\Service
 */
class PayPalPCPRequestService
{
    /**
     * @var PayPalHttpClient|null
     */
    private $client;

    /**
     * PayPalRequestService constructor.
     */
    public function __construct()
    {
        $this->client = null;
    }

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param bool $sandbox
     */
    public function setEnv(string $clientId, string $clientSecret, $sandbox = false): void
    {
        if ($sandbox) {
            /** @var SandboxEnvironment $env */
            $env = new SandboxEnvironment($clientId, $clientSecret);
        } else {
            /** @var ProductionEnvironment $env */
            $env = new ProductionEnvironment($clientId, $clientSecret);
        }
        /** @var PayPalHttpClient $client */
        $this->client = new PayPalHttpClient($env);
    }

    /**
     * @param stdClass $options
     * @return PayPalHttpRequest
     */
    public function preparePartnerReferrals(stdClass $options): PayPalHttpRequest
    {
        $request = new class() extends PayPalHttpRequest {
            /**
             *  constructor.
             */
            function __construct()
            {
                parent::__construct("/v2/customer/partner-referrals", "POST");
                $this->headers["Content-Type"] = "application/json";
            }

            /**
             * @param $payPalClientMetadataId
             */
            public function payPalClientMetadataId($payPalClientMetadataId)
            {
                $this->headers["PayPal-Client-Metadata-Id"] = $payPalClientMetadataId;
            }

            /**
             * @param $payPalRequestId
             */
            public function payPalRequestId($payPalRequestId)
            {
                $this->headers["PayPal-Request-Id"] = $payPalRequestId;
            }

            /**
             * @param $prefer
             */
            public function prefer($prefer)
            {
                $this->headers["Prefer"] = $prefer;
            }
        };

        $request->prefer('return=representation');
        $request->body = [
            'preferred_language_code' => 'ja-JP',
            'business_entity' => [
                'addresses' => [
                    0 => [
                        'country_code' => 'JP'
                    ],
                ],
            ],
            "operations" => [
                0 => [
                    'operation' => 'API_INTEGRATION',
                    'api_integration_preference' => [
                        'rest_api_integration' => [
                            'integration_method' => 'PAYPAL',
                            'integration_type' => 'FIRST_PARTY',
                            'first_party_details' => [
                                'features' => [
                                    'PAYMENT',
                                    'REFUND',
                                    'VAULT'
                                ],
                            'seller_nonce' => $options->seller_nonce,
                            ],
                        ]
                    ],
                ]
            ],
            'products' => [
                'EXPRESS_CHECKOUT'
            ],
            "legal_consents" => [
                0 => [
                    'type' => 'SHARE_DATA_CONSENT',
                    'granted' => true
                ]
            ],
        ];
        return $request;
    }

    /**
     * @param PayPalHttpRequest $request
     * @return PayPalHttpResponse
     * @throws PayPalPCPRequestException
     */
    public function partnerReferrals(PayPalHttpRequest $request): PayPalHttpResponse
    {
        /** @var PayPalHttpResponse $response */
        $response = $this->send($request);
        return new class($response) extends PayPalHttpResponse implements PartnerReferralsResponse {
            /**
             *  constructor.
             * @param PayPalHttpResponse $response
             */
            function __construct(PayPalHttpResponse $response)
            {
                parent::__construct($response->statusCode, $response->result, $response->headers);
            }

            /**
             * @return string
             */
            public function getActionUrl(): string
            {
                return $this
                    ->result
                    ->links[1]
                    ->href;
            }
        };
    }

    /**
     * @param stdClass $options
     * @return PayPalHttpRequest
     */
    public function prepareOauth2Token(stdClass $options): PayPalHttpRequest
    {
        $request = new class() extends PayPalHttpRequest {
            /**
             *  constructor.
             */
            function __construct()
            {
                parent::__construct("/v1/oauth2/token", "POST");
                $this->headers["Content-Type"] = "application/json";
            }

            /**
             * @param $payPalClientMetadataId
             */
            public function payPalClientMetadataId($payPalClientMetadataId)
            {
                $this->headers["PayPal-Client-Metadata-Id"] = $payPalClientMetadataId;
            }

            /**
             * @param $payPalRequestId
             */
            public function payPalRequestId($payPalRequestId)
            {
                $this->headers["PayPal-Request-Id"] = $payPalRequestId;
            }

            /**
             * @param $prefer
             */
            public function prefer($prefer)
            {
                $this->headers["Prefer"] = $prefer;
            }
        };

        $request->body = 'grant_type=authorization_code&code=' . $options->authCode . '&code_verifier=' . $options->seller_nonce;
        return $request;
    }

    /**
     * @param PayPalHttpRequest $request
     * @return PayPalHttpResponse
     * @throws PayPalPCPRequestException
     */
    public function oauth2Token(PayPalHttpRequest $request): PayPalHttpResponse
    {
        /** @var PayPalHttpResponse $response */
        $response = $this->send($request);
        return new class($response) extends PayPalHttpResponse implements Oauth2TokenResponse {
            /**
             *  constructor.
             * @param PayPalHttpResponse $response
             */
            function __construct(PayPalHttpResponse $response)
            {
                parent::__construct($response->statusCode, $response->result, $response->headers);
            }

            /**
             * @return string
             */
            public function getAccessToken(): string
            {
                return $this
                    ->result
                    ->access_token;
            }
        };
    }

    /**
     * @param stdClass $options
     * @return PayPalHttpRequest
     */
    public function prepareMerchantCredential(stdClass $options): PayPalHttpRequest
    {
        $request = new class() extends PayPalHttpRequest {
            /**
             *  constructor.
             */
            function __construct()
            {
                parent::__construct("", "GET");
                $this->headers["Content-Type"] = "application/json";
            }

            /**
             * @param $payPalClientMetadataId
             */
            public function payPalClientMetadataId($payPalClientMetadataId)
            {
                $this->headers["PayPal-Client-Metadata-Id"] = $payPalClientMetadataId;
            }

            /**
             * @param $payPalRequestId
             */
            public function payPalRequestId($payPalRequestId)
            {
                $this->headers["PayPal-Request-Id"] = $payPalRequestId;
            }

            /**
             * @param $prefer
             */
            public function prefer($prefer)
            {
                $this->headers["Prefer"] = $prefer;
            }

            /**
             * @param $prefer
             */
            public function authorization($authorization)
            {
                $this->headers["Authorization"] = $authorization;
            }
        };

        $request->path = "/v1/customer/partners/" . $options->partnerId . "/merchant-integrations/credentials/";
        $request->authorization("Bearer " . $options->access_token);
        return $request;
    }

    /**
     * @param PayPalHttpRequest $request
     * @return PayPalHttpResponse
     * @throws PayPalPCPRequestException
     */
    public function merchantCredential(PayPalHttpRequest $request): PayPalHttpResponse
    {
        /** @var PayPalHttpResponse $response */
        $response = $this->send($request);
        return new class($response) extends PayPalHttpResponse implements MerchantCredentialResponse {
            /**
             *  constructor.
             * @param PayPalHttpResponse $response
             */
            function __construct(PayPalHttpResponse $response)
            {
                parent::__construct($response->statusCode, $response->result, $response->headers);
            }

            /**
             * @return string
             */
            public function getClientId(): string
            {
                return $this
                    ->result
                    ->client_id;
            }

            /**
             * @return string
             */
            public function getClientSecret(): string
            {
                return $this
                    ->result
                    ->client_secret;
            }
        };
    }

    /**
     * @param PayPalHttpRequest $request
     * @return PayPalHttpResponse
     * @throws PayPalPCPRequestException
     */
    private function send(PayPalHttpRequest $request): PayPalHttpResponse
    {
        try {
            /** @var PayPalHttpResponse $response */
            $response = $this->client->execute($request);
        } catch (PayPalHttpException $e) {
            throw new PayPalPCPRequestException($e->getMessage(), $e->statusCode, $e);
        }
        return $response;
    }
}
