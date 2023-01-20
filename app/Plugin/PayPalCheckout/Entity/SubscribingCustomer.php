<?php

namespace Plugin\PayPalCheckout\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\Pref;
use Eccube\Entity\ProductClass;
use Eccube\Repository\Master\PrefRepository;

/**
 * Class SubscribingCustomer
 * @package Plugin\PayPalCheckout\Entity
 *
 * @ORM\Table(name="plg_paypal_subscribing_customer")
 * @ORM\Entity(repositoryClass="Plugin\PayPalCheckout\Repository\SubscribingCustomerRepository")
 */
class SubscribingCustomer
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
     * @var integer
     *
     * @ORM\Column(name="customer_id", type="integer", options={"unsigned":true, "default":null})
     */
    private $customer_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="product_class_id", type="integer", options={"unsigned":true, "default":null})
     */
    private $product_class_id;

    /**
     * @var Customer
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer", inversedBy="SubscribingCustomer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     * })
     */
    private $Customer;

    /**
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\ProductClass", inversedBy="SubscribingCustomer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_class_id", referencedColumnName="id")
     * })
     */
    private $ProductClass;

    /**
     * @var Transaction
     * @ORM\ManyToOne(targetEntity="Transaction", inversedBy="SubscribingCustomer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="reference_transaction_id", referencedColumnName="id")
     * })
     */
    private $ReferenceTransaction;

    /**
     * @ORM\Column(name="primary_shipping_address", type="json_array", length=2048, nullable=true)
     */
    private $primary_shipping_address;

    /**
     * @ORM\Column(name="error_message", type="string", length=255, nullable=true)
     */
    private $error_message;

    /**
     * @var DateTime
     * @ORM\Column(name="next_payment_date", type="datetimetz", nullable=true)
     */
    private $next_payment_date;

    /**
     * @ORM\Column(name="contracted_at", type="datetimetz", nullable=true)
     */
    private $contracted_at;

    /**
     * @ORM\Column(name="withdrawal_at", type="datetimetz", nullable=true)
     */
    private $withdrawal_at;

    /**
     * SubscribingCustomer constructor.
     * @param Customer $customer
     * @param ProductClass $productClass
     * @param Transaction $transaction
     * @throws \Exception
     */
    private function __construct(Customer $customer, ProductClass $productClass, Transaction $transaction)
    {
        $this->Customer = $customer;
        $this->ProductClass = $productClass;
        $this->ReferenceTransaction = $transaction;
        $this->next_payment_date = (new DateTime())->modify("+ 1 months");
        $this->contracted_at = new DateTime();
    }

    /**
     * @param Customer $customer
     * @param ProductClass $productClass
     * @param Transaction $transaction
     * @return SubscribingCustomer
     * @throws \Exception
     */
    public static function create(Customer $customer, ProductClass $productClass, Transaction $transaction): SubscribingCustomer
    {
        if ($productClass->getUseSubscription()) {
            $instance = new static ($customer, $productClass, $transaction);
            return $instance;
        }
    }

    /**
     * @param int $referenceDay
     * @param int $cutOffDay
     * @return DateTime
     */
    public function calculateNextPaymentDate(int $referenceDay, int $cutOffDay): DateTime
    {
        /** @var DateTime $nextPaymentDate */
        $nextPaymentDate = clone $this->next_payment_date;

        /** @var int $base */
        $base = (int) $nextPaymentDate->format('d');
        if ($base < $cutOffDay) {
            $nextPaymentDate->modify('first day of next months');
        } else {
            $nextPaymentDate->modify('first day of + 2 months');
        }
        $referenceDay--;
        $nextPaymentDate->modify("+ ${referenceDay} days");
        return $nextPaymentDate;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    /**
     * @return int
     */
    public function getProductClassId()
    {
        return $this->product_class_id;
    }

    /**
     * @param Transaction $transaction
     */
    public function setReferenceTransaction(Transaction $transaction): void
    {
        $this->ReferenceTransaction = $transaction;
    }

    /**
     * @return Transaction
     */
    public function getReferenceTransaction(): Transaction
    {
        return $this->ReferenceTransaction;
    }

    /**
     * @param DateTime $date
     */
    public function setNextPaymentDate($date)
    {
        $this->next_payment_date = $date;
    }

    /**
     * @return DateTime
     */
    public function getNextPaymentDate(): DateTime
    {
        return $this->next_payment_date;
    }

    /**
     * @return Customer
     */
    public function getCustomer(): Customer
    {
        return $this->Customer;
    }

    /**
     * @return ProductClass
     */
    public function getProductClass(): ProductClass
    {
        return $this->ProductClass;
    }

    /**
     * @param DateTime $date
     */
    public function setWithdrawalAt($date)
    {
        $this->withdrawal_at = $date;
    }

    /**
     * @return DateTime
     */
    public function getWithdrawalAt(): DateTime
    {
        return $this->withdrawal_at;
    }

    /**
     * @return ConfigSubscription
     */
    public function createConfigSubscription(): ConfigSubscription
    {
        /** @var ConfigSubscription $configSubscription */
        $configSubscription = new ConfigSubscription();

        /** @var bool $deleted */
        $deleted = is_null($this->withdrawal_at) ? false : true;
        $configSubscription->setIsDeleted($deleted);
        $configSubscription->setNextPaymentDate($this->next_payment_date);

        return $configSubscription;
    }

    /**
     * @param $errorMessage
     */
    public function setErrorMessage($errorMessage)
    {
        $this->error_message = $errorMessage;
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

    /**
     * @return PrimaryShippingAddress
     */
    public function getPrimaryShippingAddress(): PrimaryShippingAddress
    {
        /** @var array|null $values */
        $values = $this->primary_shipping_address;

        /** @var Customer $Customer */
        $Customer = $this->ReferenceTransaction->getOrder()->getCustomer();

        /** @var PrimaryShippingAddress $PrimaryShippingAddress */
        $PrimaryShippingAddress = new PrimaryShippingAddress();
        $PrimaryShippingAddress
            ->setFirstName($values['first_name'] ?? $Customer->getName02())
            ->setLastName($values['last_name'] ?? $Customer->getName01())
            ->setFirstNameKana($values['first_name_kana'] ?? $Customer->getKana02())
            ->setLastNameKana($values['last_name_kana'] ?? $Customer->getKana01())
            ->setPref($values['pref'] ?? $Customer->getPref()->getName())
            ->setPostalCode($values['postal_code'] ?? $Customer->getPostalCode())
            ->setAddress1($values['address1'] ?? $Customer->getAddr01())
            ->setAddress2($values['address2'] ?? $Customer->getAddr02())
            ->setPhoneNumber($values['phone_number'] ?? $Customer->getPhoneNumber())
            ->setCompanyName($values['company_name'] ?? $Customer->getCompanyName());

        return $PrimaryShippingAddress;
    }

    /**
     * @param PrefRepository $prefRepository
     * @return Customer|null
     */
    public function getPrimaryCustomer(PrefRepository $prefRepository): ?Customer
    {
        /** @var array $values */
        $values = $this->primary_shipping_address;
        if (count($values) !== 0) {
            /** @var Pref $Pref */
            $Pref = $prefRepository->findOneBy(['name' => $values['pref']]);
            return $this->Customer
                ->setName01($values['last_name'])
                ->setName02($values['first_name'])
                ->setKana01($values['last_name_kana'])
                ->setKana02($values['first_name_kana'])
                ->setCompanyName($values['company_name'])
                ->setPhonenumber($values['phone_number'])
                ->setPostalcode($values['postal_code'])
                ->setPref($Pref)
                ->setAddr01($values['address1'])
                ->setAddr02($values['address2']);
        }
        return null;
    }

    /**
     * @param PrimaryShippingAddress $PrimaryShippingAddress
     */
    public function setPrimaryShippingAddress(PrimaryShippingAddress $PrimaryShippingAddress): void
    {
        $values = [
            'first_name' => $PrimaryShippingAddress->getFirstName(),
            'last_name' => $PrimaryShippingAddress->getLastName(),
            'first_name_kana' => $PrimaryShippingAddress->getFirstNameKana(),
            'last_name_kana' => $PrimaryShippingAddress->getLastNameKana(),
            'pref' => $PrimaryShippingAddress->getPref(),
            'postal_code' => $PrimaryShippingAddress->getPostalCode(),
            'address1' => $PrimaryShippingAddress->getAddress1(),
            'address2' => $PrimaryShippingAddress->getAddress2(),
            'phone_number' => $PrimaryShippingAddress->getPhoneNumber(),
            'company_name' => $PrimaryShippingAddress->getCompanyName(),
        ];
        $this->primary_shipping_address = $values;
    }
}
