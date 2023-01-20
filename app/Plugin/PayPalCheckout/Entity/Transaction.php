<?php

namespace Plugin\PayPalCheckout\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Order;
use Plugin\PayPalCheckout\Contracts\CaptureTransactionResponse;
use Plugin\PayPalCheckout\Contracts\ReferenceTransactionResponse;
use stdClass;

/**
 * Class Transaction
 * @package Plugin\PayPalCheckout\Entity
 *
 * @ORM\Table(name="plg_paypal_transaction")
 * @ORM\Entity(repositoryClass="Plugin\PayPalCheckout\Repository\TransactionRepository")
 */
class Transaction
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Order
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Order", inversedBy="Transaction")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     * })
     */
    private $Order;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\PayPalCheckout\Entity\SubscribingCustomer", mappedBy="Transaction", cascade={"persist", "remove"})
     */
    private $SubscribingCustomers;

    /**
     * @var string
     *
     * @ORM\Column(name="status_code", type="string", length=255, nullable=true)
     */
    private $status_code;

    /**
     * @var string
     *
     * @ORM\Column(name="paypal_debug_id", type="string", length=255, nullable=true)
     */
    private $paypal_debug_id;

    /**
     * @ORM\Column(name="billing_agreement_id", type="string", length=255, nullable=true)
     */
    private $billing_agreement_id;

    /**
     * @ORM\Column(name="capture_id", type="string", length=255, nullable=true)
     */
    private $capture_id;

    // 返金id

    /**
     * Transaction constructor.
     * @param Order $order
     * @param $response
     * @param stdClass $params
     */
    private function __construct(Order $order, $response, stdClass $params)
    {
        $this->Order = $order;
        $this->status_code = $response->statusCode;
        $this->paypal_debug_id = $response->headers['Paypal-Debug-Id'];
        $this->billing_agreement_id = $params->billing_agreement_id ?? null;
        $this->capture_id = $params->capture_id ?? null;
    }

    /**
     * @param Order $order
     * @param $response
     * @return Transaction
     */
    public static function create(Order $order, $response): Transaction
    {
        /** @var stdClass $params */
        $params = new stdClass();
        if (in_array(ReferenceTransactionResponse::class, class_implements($response))) {
            /** @var ReferenceTransactionResponse $referenceTransactionResponse */
            $referenceTransactionResponse = $response;
            $params->billing_agreement_id = $referenceTransactionResponse->getBillingAgreementId();
        } elseif (in_array(CaptureTransactionResponse::class, class_implements($response))) {
            /** @var CaptureTransactionResponse $captureTransactionResponse */
            $captureTransactionResponse = $response;
            $params->capture_id = $captureTransactionResponse->getCaptureTransactionId();
        } else {
            // do nothing
        }
        return new static ($order, $response, $params);
    }

    /**
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->Order;
    }

    /**
     * @return string
     */
    public function getBillingAgreementId(): string
    {
        return $this->billing_agreement_id;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCaptureId(): string
    {
        return $this->capture_id;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
    }
}
