<?php

namespace Plugin\PayPalCheckout\Service;

use Plugin\PayPalCheckout\Contracts\GenerateClientTokenResponse;
use Plugin\PayPalCheckout\Contracts\GeneratePaymentTokenResponse;
use Plugin\PayPalCheckout\Lib\PayPalHttp\HttpException;
use Plugin\PayPalCheckout\Lib\PayPalHttp\HttpRequest as PayPalHttpRequest;
use Plugin\PayPalCheckout\Lib\PayPalHttp\HttpResponse as PayPalHttpResponse;
use Plugin\PayPalCheckout\Lib\PayPalCheckoutSdk\Core\PayPalHttpClient;
use Plugin\PayPalCheckout\Lib\PayPalCheckoutSdk\Core\ProductionEnvironment;
use Plugin\PayPalCheckout\Lib\PayPalCheckoutSdk\Core\SandboxEnvironment;
use Plugin\PayPalCheckout\Exception\PayPalRequestException;

/**
 * Class PayPalAcdcRequestService
 * @package Plugin\PayPalCheckout\Service
 */
class PayPalAcdcRequestService
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
     * クライアントトークン取得のためのリクエストを作成します。
     * 顧客IDを指定した場合は Vault 機能を使って顧客ごとにクレジットカード情報を保存することができます。
     *
     * @param string $customerId 一意に識別できる顧客IDを指定します
     * @return PayPalHttpRequest
     */
    public function prepareClientToken($customerId = null): PayPalHttpRequest
    {
        $request = new class($customerId) extends PayPalHttpRequest {
            function __construct($customerId = null)
            {
                parent::__construct("/v1/identity/generate-token", "POST");
                // Authorization ヘッダーは Client 側で付与してくれるので不要
                $this->headers["Content-Type"] = "application/json";
                $this->headers["Accept-Language"] = "en_US";
                if (!is_null($customerId)) {
                    $this->body = [
                        'customer_id' => $customerId
                    ];
                }
            }
        };
        return $request;
    }

    /**
     * クライアントトークンを取得します
     *
     * @param PayPalHttpRequest $request リクエスト
     * @return PayPalHttpResponse レスポンス
     * @throws PayPalRequestException
     */
    public function getClientToken(PayPalHttpRequest $request): PayPalHttpResponse
    {
        /** @var PayPalHttpResponse $response */
        $response = $this->send($request);

        return new class($response) extends PayPalHttpResponse implements GenerateClientTokenResponse
        {
            function __construct(PayPalHttpResponse $response)
            {
                parent::__construct($response->statusCode, $response->result, $response->headers);
            }
            public function getClientToken(): string
            {
                return $this->result->client_token;
            }
        };
    }

    /**
     * ペイメントトークン取得のためのリクエストを作成します。
     *
     * @param string $customerId 一意に識別できる顧客IDを指定します
     * @return PayPalHttpRequest
     */
    public function preparePaymentToken(string $customerId): PayPalHttpRequest
    {
        $request = new class($customerId) extends PayPalHttpRequest {
            function __construct($customerId)
            {
                parent::__construct("/v2/vault/payment-tokens?customer_id={$customerId}", "GET");
                // Authorization ヘッダーは Client 側で付与してくれるので不要
                $this->headers["Content-Type"] = "application/json";
            }
        };
        return $request;
    }

    /**
     * ペイメントトークンを取得します
     *
     * @param PayPalHttpRequest $request リクエスト
     * @return PayPalHttpResponse レスポンス
     * @throws PayPalRequestException
     */
    public function getPaymentToken(PayPalHttpRequest $request): PayPalHttpResponse
    {
        /** @var PayPalHttpResponse $response */
        $response = $this->send($request);

        return new class($response) extends PayPalHttpResponse implements GeneratePaymentTokenResponse
        {
            function __construct(PayPalHttpResponse $response)
            {
                parent::__construct($response->statusCode, $response->result, $response->headers);
            }

            /**
             * @return array
             */
            public function getPaymentTokens(): array
            {
                return $this->result->payment_tokens ?? [];
            }
        };
    }

    /**
     * vault 削除のためのリクエストを作成します。
     *
     * @param string $vaultId 削除する vault ID
     * @return PayPalHttpRequest
     */
    public function prepareDeleteVault($vaultId): PayPalHttpRequest
    {
        $request = new class($vaultId) extends PayPalHttpRequest {
            function __construct($vaultId = null)
            {
                parent::__construct("/v2/vault/payment-tokens/{$vaultId}", "DELETE");
                // Authorization ヘッダーは Client 側で付与してくれるので不要
                $this->headers["Content-Type"] = "application/json";
            }
        };
        return $request;
    }

    /**
     * vault を削除します
     *
     * @param PayPalHttpRequest $request リクエスト
     * @return bool 成否
     * @throws PayPalRequestException
     */
    public function deleteVault(PayPalHttpRequest $request): bool
    {
        /** @var PayPalHttpResponse $response */
        $response = $this->send($request);
        return 200 <= $response->statusCode && $response->statusCode <= 299;
    }

    /**
     * @param PayPalHttpRequest $request
     * @return PayPalHttpResponse
     * @throws PayPalRequestException
     */
    private function send(PayPalHttpRequest $request): PayPalHttpResponse
    {
        try {
            /** @var PayPalHttpResponse $response */
            $response = $this->client->execute($request);
        } catch (HttpException $e) {
            throw new PayPalRequestException($e->getMessage(), $e->statusCode, $e);
        }
        return $response;
    }
}
