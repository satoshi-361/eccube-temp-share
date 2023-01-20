<?php

namespace Plugin\PayPalCheckout\Entity;

use DateTime;

/**
 * Class ConfigSubscription
 * @package Plugin\PayPalCheckout\Entity
 */
class ConfigSubscription
{
    /**
     * @var DateTime
     */
    private $next_payment_date;

    /**
     * @var bool
     */
    private $is_deleted;

    /**
     * @return DateTime
     */
    public function getNextPaymentDate()
    {
        return $this->next_payment_date;
    }

    /**
     * @param $next_payment_date
     * @return $this
     */
    public function setNextPaymentDate($next_payment_date)
    {
        $this->next_payment_date = $next_payment_date;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsDeleted()
    {
        return $this->is_deleted;
    }

    /**
     * @param $is_deleted
     * @return $this
     */
    public function setIsDeleted($is_deleted)
    {
        $this->is_deleted = $is_deleted;
        return $this;
    }
}
