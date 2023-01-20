<?php

namespace Plugin\PayPalCheckout\Service;

use Plugin\PayPalCheckout\Contracts\GenerateClientTokenResponse;
use Plugin\PayPalCheckout\Contracts\GeneratePaymentTokenResponse;
use Plugin\PayPalCheckout\Entity\Config;
use Plugin\PayPalCheckout\Exception\PayPalCheckoutException;
use Plugin\PayPalCheckout\Repository\ConfigRepository;
use Plugin\PayPalCheckout\Util\StringUtil;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class PayPalAcdcService
 * @package Plugin\PayPalCheckout\Service
 */
class PayPalAcdcService
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var PayPalAcdcRequestService
     */
    private $client;

    /**
     * @var LoginService
     */
    private $loginService;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var StringUtil
     */
    private $stringUtil;

    /**
     * PayPalService constructor.
     * @param SessionInterface $session
     * @param PayPalAcdcRequestService $PayPalAcdcRequestService
     * @param LoginService $loginService
     * @param LoggerService $loggerService
     * @param ConfigRepository $configRepository
     * @param StringUtil $stringUtil
     */
    public function __construct(
        SessionInterface $session,
        PayPalAcdcRequestService $PayPalAcdcRequestService,
        LoginService $loginService,
        LoggerService $loggerService,
        ConfigRepository $configRepository,
        StringUtil $stringUtil
    ) {
        $this->session = $session;
        $this->config = $configRepository->get();
        $this->client = $PayPalAcdcRequestService;
        $isSandbox = $this->config->getUseSandbox();
        $this->client->setEnv($this->config->getClientId(), $this->config->getClientSecret(), $isSandbox);
        $this->loginService = $loginService;
        $this->logger = $loggerService;
        $this->stringUtil = $stringUtil;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->eccubeConfig->get('paypal.debug') ?? false;
    }

    /**
     * vaultが利用できるか否かを返す
     *
     * @return bool
     */
    public function canUseVault(): bool
    {
        return $this->config->getUseVault() && $this->loginService->isLoginUser();
    }

    /**
     * @param string $vaultId
     */
    public function setVaultIdToSession(string $vaultId): void
    {
        $this->session->set('createOrderRequest.vaultId', $vaultId);
    }

    /**
     * @return string $vaultId
     */
    public function getVaultIdFromSession(): string
    {
        return $this->session->get('createOrderRequest.vaultId', '');
    }

    /**
     * vault id を取り出して消す
     *
     * @return string
     */
    public function extractVaultIdFromSession(): string
    {
        $vaultId = $this->session->get('createOrderRequest.vaultId', '');
        $this->session->remove('createOrderRequest.vaultId');
        return $vaultId;
    }

    /**
     * vault に保存するか否かを保存する
     *
     * @param string $saveVault
     */
    public function setSaveVaultToSession(string $saveVault): void
    {
        $this->session->set('createOrderRequest.saveVault', $saveVault === '1');
    }

    /**
     * FraudNetで使用する、セッション毎のIDを生成し保存する
     *
     * @return string
     */
    public function createAndSaveFraudNetSessionIdentifierToSession(): string
    {
        $identifier = $this->stringUtil::createRandomString(30);
        $this->session->set('createOrderRequest.fraudNetSessionIdentifier', $identifier);
        return $identifier;
    }

    /**
     * FraudNetで使用する、セッション毎のIDを取得し削除する
     *
     * @return string
     */
    public function extractFraudNetSessionIdentifierFromSession(): string
    {
        $identifier = $this->session->get('createOrderRequest.fraudNetSessionIdentifier', false);
        $this->session->remove('createOrderRequest.fraudNetSessionIdentifier');
        return $identifier;
    }

    /**
     * FraudNetで使用する、サイトごとに一意のIDを取得する
     *
     * @return string
     */
    public function getSourceWebsiteIdentifier(): string
    {
        // 「ECCUBE_クライアントIDの先頭13文字_BA」のルールで生成
        $clientId = substr($this->config->getClientId(), 0, 13);
        return "ECCUBE_${clientId}_BA";
    }

    /**
     * vault を保存するか否かを取り出して消す
     *
     * @return bool
     */
    public function extractSaveVaultFromSession(): bool
    {
        $saveVault = $this->session->get('createOrderRequest.saveVault', false);
        $this->session->remove('createOrderRequest.saveVault');
        return $saveVault;
    }

    /**
     * 現在存在する vault を一括削除します
     */
    public function bulkDeleteExistingVault(): void
    {
        $vaults = $this->getVaults();
        if (count($vaults) === 0) {
            return;
        }
        // 全て消し切るまで削除を繰り返す
        $retryCount = 3;
        $i = 0;
        do {
            foreach ($vaults as $vault) {
                $this->deleteVaultFromId($vault['id']);
            }
            $vaults = $this->getVaults();
            if (count($vaults) === 0) {
                break;
            }
            $i++;
        } while ($i < $retryCount);
        // リトライ回数すべて失敗した場合にエラー
        if ($i >= $retryCount) {
            throw new PayPalCheckoutException('vault の削除に失敗しました');
        }
    }

    /**
     * 指定の vault id のデータを削除します
     *
     * @param $vaultId
     * @return bool
     * @throws PayPalCheckoutException
     * @throws \Plugin\PayPalCheckout\Exception\PayPalRequestException
     */
    public function deleteVaultFromId($vaultId): bool
    {
        $request = $this->client->prepareDeleteVault($vaultId);
        return $this->client->deleteVault($request);
    }

    /**
     * クライアントトークンを取得する
     *
     * @param string $useVault vaultを利用するか否か
     * @return string
     * @throws \Plugin\PayPalCheckout\Exception\PayPalRequestException
     */
    public function getClientToken($useVault = false): string
    {
        $customerId = null;
        if ($useVault && $this->canUseVault()) {
            $customerId = $this->generateCustomerIdForVault();
        }
        $request = $this->client->prepareClientToken($customerId);
        /** @var GenerateClientTokenResponse $response */
        $response = $this->client->getClientToken($request);
        return $response->getClientToken();
    }

    /**
     * @return ?array
     * @throws \Plugin\PayPalCheckout\Exception\PayPalRequestException
     */
    public function getVaults(): array
    {
        if (!$this->canUseVault()) {
            return [];
        }
        $customerId = $this->generateCustomerIdForVault();
        $request = $this->client->preparePaymentToken($customerId);
        /** @var GeneratePaymentTokenResponse $response */
        $response = $this->client->getPaymentToken($request);
        $paymentTokens = $response->getPaymentTokens();
        return json_decode(json_encode($paymentTokens), true);
    }

    /**
     * vault 利用のための顧客IDを生成する
     *
     * 顧客IDは複数サイトで一意となる必要がある(PayPalのクライアンドIDが使いまわされる可能性があるため)。
     * サイト一意の文字列 + 顧客一意の文字列 をキーとする。
     *
     * サイト一意の文字列：ECCUBE_AUTH_MAGIC
     *     ドメイン名やIPアドレスは変更される可能性があるため、 site_unique_key を利用する。
     *     文字数制限的にここの文字列が多すぎると顧客IDの数を圧迫してしまうが、12文字までにすることで10億の顧客まで対応できるため問題ないと考える。
     * 顧客一意の文字列：customer_id
     *     メールアドレスや電話番号は変更される可能性があるため、サイト内で一意となる customer_id を使う。
     *
     * @return string|null
     */
    private function generateCustomerIdForVault()
    {
        $customer = $this->loginService->getCustomer();
        $customerId = $customer->getId() ?? null;
        if (empty($customerId)) {
            return null;
        }
        $siteUniqueKey = substr($this->config->getSiteUniqueKey(), 0, 12);
        return $siteUniqueKey.$customerId;
    }
}
