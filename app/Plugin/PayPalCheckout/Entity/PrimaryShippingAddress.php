<?php

namespace Plugin\PayPalCheckout\Entity;

/**
 * Class PrimaryShippingAddress
 * @package Plugin\PayPalCheckout\Entity
 */
class PrimaryShippingAddress
{
    /**
     * @var string
     */
    private $first_name;

    /**
     * @var string
     */
    private $last_name;

    /**
     * @var string
     */
    private $first_name_kana;

    /**
     * @var string
     */
    private $last_name_kana;

    /**
     * @var string
     */
    private $pref;

    /**
     * @var string
     */
    private $postal_code;

    /**
     * @var string
     */
    private $address1;

    /**
     * @var string
     */
    private $address2;

    /**
     * @var string
     */
    private $phone_number;

    /**
     * @var string
     */
    private $company_name;

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * @param $first_name
     * @return $this
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * @param $last_name
     * @return $this
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;
        return $this;
    }

    /**
     * @return string
     */
    public function getFirstNameKana()
    {
        return $this->first_name_kana;
    }

    /**
     * @param $first_name_kana
     * @return $this
     */
    public function setFirstNameKana($first_name_kana)
    {
        $this->first_name_kana = $first_name_kana;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastNameKana()
    {
        return $this->last_name_kana;
    }

    /**
     * @param $last_name_kana
     * @return $this
     */
    public function setLastNameKana($last_name_kana)
    {
        $this->last_name_kana = $last_name_kana;
        return $this;
    }

    /**
     * @return string
     */
    public function getPref()
    {
        return $this->pref;
    }

    /**
     * @param $pref
     * @return PrimaryShippingAddress
     */
    public function setPref($pref)
    {
        $this->pref = $pref;
        return $this;
    }

    /**
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postal_code;
    }

    /**
     * @param $postal_code
     * @return $this
     */
    public function setPostalCode($postal_code)
    {
        $this->postal_code = $postal_code;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddress1()
    {
        return $this->address1;
    }

    /**
     * @param $address1
     * @return $this
     */
    public function setAddress1($address1)
    {
        $this->address1 = $address1;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * @param $address2
     * @return $this
     */
    public function setAddress2($address2)
    {
        $this->address2 = $address2;
        return $this;
    }

    /**
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phone_number;
    }

    /**
     * @param $phone_number
     * @return $this
     */
    public function setPhoneNumber($phone_number)
    {
        $this->phone_number = $phone_number;
        return $this;
    }

    /**
     * @return string
     */
    public function getCompanyName()
    {
        return $this->company_name;
    }

    /**
     * @param $company_name
     * @return $this
     */
    public function setCompanyName($company_name)
    {
        $this->company_name = $company_name;
        return $this;
    }
}
