<?php

namespace Plugin\PayPalCheckout\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * Trait OrderTrait
 * @package Plugin\PayPalCheckout\Entity
 *
 * @EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{
    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\PayPalCheckout\Entity\Transaction", mappedBy="Order", cascade={"persist", "remove"})
     */
    private $Transaction;

    private $yamatoOrderPayment;

    private $yamatoOrderStatus;

    /**
     * @param Transaction|null $transaction
     * @return $this
     */
    public function setTransaction(Transaction $transaction = null): self
    {
        $this->Transaction = $transaction;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getTransaction()
    {
        return $this->Transaction;
    }
}
