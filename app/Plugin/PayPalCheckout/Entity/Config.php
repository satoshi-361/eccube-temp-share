<?php

namespace Plugin\PayPalCheckout\Entity;

use Doctrine\ORM\Mapping as ORM;
use Plugin\PayPalCheckout\Util\StringUtil;
use stdClass;

/**
 * Config
 *
 * @ORM\Table(name="plg_paypal_config")
 * @ORM\Entity(repositoryClass="Plugin\PayPalCheckout\Repository\ConfigRepository")
 */
class Config
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="client_id", type="string", length=255)
     */
    private $client_id;

    /**
     * @var string
     *
     * @ORM\Column(name="client_secret", type="string", length=255)
     */
    private $client_secret;

    /**
     * @var boolean
     *
     * @ORM\Column(name="use_sandbox", type="boolean", options={"default":true})
     */
    private $use_sandbox;

    /**
     * @var string
     *
     * @ORM\Column(name="paypal_logo", type="string", length=255)
     */
    private $paypal_logo;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_paypal_logo", type="string", length=255)
     */
    private $payment_paypal_logo;

    /**
     * @var boolean
     *
     * @ORM\Column(name="use_express_btn", type="boolean", options={"default":false})
     */
    private $use_express_btn;

    /**
     * @var boolean
     *
     * @ORM\Column(name="use_vault", type="boolean", options={"default":false})
     */
    private $use_vault;

    /**
     * @var boolean
     *
     * @ORM\Column(name="use_3dsecure", type="boolean", options={"default":false})
     */
    private $use_3dsecure;

    /**
     * @var int|null
     *
     * @ORM\Column(name="reference_day", type="integer", nullable=true)
     */
    private $reference_day;

    /**
     * @var int|null
     *
     * @ORM\Column(name="cut_off_day", type="integer", nullable=true)
     */
    private $cut_off_day;

    /**
     * @var string
     *
     * @ORM\Column(name="site_unique_key", type="string", length=255)
     */
    private $site_unique_key;

    /**
     * Config constructor.
     * @param stdClass $params
     */
    private function __construct(stdClass $params)
    {
        $this->client_id = $params->client_id;
        $this->client_secret = $params->client_secret;
        $this->use_sandbox = $params->use_sandbox;
        $this->use_express_btn = $params->use_express_btn;
        $this->paypal_logo = $params->paypal_logo;
        $this->payment_paypal_logo = $params->payment_paypal_logo;
        $this->reference_day = $params->reference_day;
        $this->cut_off_day = $params->cut_off_day;
        $this->use_express_btn = $params->use_express_btn;
        $this->use_vault = $params->use_vault;
        $this->use_3dsecure = $params->use_3dsecure;
        $this->site_unique_key = $params->site_unique_key;
    }

    /**
     * @return Config
     */
    public static function createInitialConfig(): Config
    {
        /** @var stdClass $params */
        $params = new stdClass();
        $params->client_id = '';
        $params->client_secret = '';
        $params->use_sandbox = true;
        $params->use_express_btn = true;
        $params->use_vault = false;
        $params->use_3dsecure = false;
        $params->paypal_logo = 1;
        $params->payment_paypal_logo = 1;
        $params->reference_day = 2;
        $params->cut_off_day = 15;
        $params->site_unique_key = StringUtil::createRandomString();
        return new static($params);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->client_secret;
    }

    /**
     * Get use_sandbox
     *
     * @return boolean
     */
    public function getUseSandbox()
    {
        return $this->use_sandbox;
    }

    /**
     * Get paypal_logo
     *
     * @return string
     */
    public function getPaypalLogo()
    {
        return $this->paypal_logo;
    }

    /**
     * Get payment_paypal_logo
     *
     * @return string
     */
    public function getPaymentPaypalLogo()
    {
        return $this->payment_paypal_logo;
    }

    /**
     * @return int|null
     */
    public function getReferenceDay()
    {
        return $this->reference_day;
    }

    /**
     * @return int|null
     */
    public function getCutOffDay()
    {
        return $this->cut_off_day;
    }

    /**
     * @return bool
     */
    public function getUseExpressBtn()
    {
        return $this->use_express_btn;
    }

    /**
     * @return bool
     */
    public function getUseVault()
    {
        return $this->use_vault;
    }

    /**
     * @return bool
     */
    public function getUse3dsecure()
    {
        return $this->use_3dsecure;
    }

    /**
     * @return string
     */
    public function getSiteUniqueKey()
    {
        return $this->site_unique_key;
    }

    /**
     * @return PluginSetting
     */
    public function createPluginSettingForm(): PluginSetting
    {
        /** @var PluginSetting $pluginSetting */
        $pluginSetting = new PluginSetting();
        $pluginSetting
            ->setClientId($this->client_id)
            ->setClientSecret($this->client_secret)
            ->setUseSandbox($this->use_sandbox)
            ->setPaypalLogo($this->paypal_logo)
            ->setPaymentPaypalLogo($this->payment_paypal_logo)
            ->setReferenceDay($this->reference_day)
            ->setCutOffDay($this->cut_off_day)
            ->setUseExpressBtn($this->use_express_btn)
            ->setUseVault($this->use_vault)
            ->setUse3dsecure($this->use_3dsecure);

        return $pluginSetting;
    }

    /**
     * @param PluginSetting $pluginSetting
     */
    public function savePluginSetting(PluginSetting $pluginSetting): void
    {
        $this->client_id = $pluginSetting->getClientId();
        $this->client_secret = $pluginSetting->getClientSecret();
        $this->use_sandbox = $pluginSetting->getUseSandbox();
        $this->paypal_logo = $pluginSetting->getPaypalLogo();
        $this->payment_paypal_logo = $pluginSetting->getPaymentPaypalLogo();
        $this->reference_day = $pluginSetting->getReferenceDay();
        $this->cut_off_day = $pluginSetting->getCutOffDay();
        $this->use_express_btn = $pluginSetting->getUseExpressBtn();
        $this->use_vault = $pluginSetting->getUseVault();
        $this->use_3dsecure = $pluginSetting->getUse3dsecure();
    }
}
