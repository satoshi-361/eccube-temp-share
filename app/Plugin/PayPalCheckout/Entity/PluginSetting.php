<?php

namespace Plugin\PayPalCheckout\Entity;

/**
 * Class PluginSetting
 * @package Plugin\PayPalCheckout\Entity
 */
class PluginSetting
{
    /**
     * @var string
     */
    private $client_id;

    /**
     * @var string
     */
    private $client_secret;

    /**
     * @var bool
     */
    private $use_sandbox;

    /**
     * @var string
     */
    private $paypal_logo;

    /**
     * @var string
     */
    private $payment_paypal_logo;

    /**
     * @var int
     */
    private $reference_day;

    /**
     * @var int
     */
    private $cut_off_day;

    /**
     * @var bool
     */
    private $use_express_btn;

    /**
     * @var bool
     */
    private $use_vault;

    /**
     * @var bool
     */
    private $use_3dsecure;

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * @param $client_id
     * @return $this
     */
    public function setClientId($client_id)
    {
        $this->client_id = $client_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->client_secret;
    }

    /**
     * @param $client_secret
     * @return $this
     */
    public function setClientSecret($client_secret)
    {
        $this->client_secret = $client_secret;
        return $this;
    }

    /**
     * @return bool
     */
    public function getUseSandbox()
    {
        return $this->use_sandbox ?? false;
    }

    /**
     * @param $use_sandbox
     * @return $this
     */
    public function setUseSandbox($use_sandbox)
    {
        $this->use_sandbox = $use_sandbox;
        return $this;
    }

    /**
     * @return string
     */
    public function getPaypalLogo()
    {
        return $this->paypal_logo;
    }

    /**
     * @param $paypal_logo
     * @return $this
     */
    public function setPaypalLogo($paypal_logo)
    {
        $this->paypal_logo = $paypal_logo;
        return $this;
    }

    /**
     * @return string
     */
    public function getPaymentPaypalLogo()
    {
        return $this->payment_paypal_logo;
    }

    /**
     * @param $payment_paypal_logo
     * @return $this
     */
    public function setPaymentPaypalLogo($payment_paypal_logo)
    {
        $this->payment_paypal_logo = $payment_paypal_logo;
        return $this;
    }

    /**
     * @return int
     */
    public function getReferenceDay()
    {
        return $this->reference_day;
    }

    /**
     * @param int $reference_day
     * @return PluginSetting
     */
    public function setReferenceDay(int $reference_day)
    {
        $this->reference_day = $reference_day;
        return $this;
    }

    /**
     * @return int
     */
    public function getCutOffDay()
    {
        return $this->cut_off_day;
    }

    /**
     * @param int $cut_off_day
     * @return PluginSetting
     */
    public function setCutOffDay(int $cut_off_day)
    {
        $this->cut_off_day = $cut_off_day;
        return $this;
    }

    /**
     * @return bool
     */
    public function getUseExpressBtn()
    {
        return $this->use_express_btn;
    }

    /**
     * @param bool $use_express_btn
     * @return $this
     */
    public function setUseExpressBtn($use_express_btn)
    {
        $this->use_express_btn = $use_express_btn;
        return $this;
    }

    /**
     * @return bool
     */
    public function getUseVault()
    {
        return $this->use_vault;
    }

    /**
     * @param bool $use_vault
     * @return $this
     */
    public function setUseVault($use_vault)
    {
        $this->use_vault = $use_vault;
        return $this;
    }

    /**
     * @return bool
     */
    public function getUse3dsecure()
    {
        return $this->use_3dsecure;
    }

    /**
     * @param bool $use_3dsecure
     * @return $this
     */
    public function setUse3dsecure($use_3dsecure)
    {
        $this->use_3dsecure = $use_3dsecure;
        return $this;
    }
}
