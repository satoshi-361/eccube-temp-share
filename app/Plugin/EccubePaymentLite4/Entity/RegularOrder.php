<?php

namespace Plugin\EccubePaymentLite4\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\Country;
use Eccube\Entity\Master\CustomerOrderStatus;
use Eccube\Entity\Master\DeviceType;
use Eccube\Entity\Master\Job;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Master\OrderStatusColor;
use Eccube\Entity\Master\Pref;
use Eccube\Entity\Master\Sex;
use Eccube\Entity\Master\TaxType;
use Eccube\Entity\NameTrait;
use Eccube\Entity\Order;
use Eccube\Entity\Payment;
use Eccube\Entity\PointTrait;
use Eccube\Service\Calculator\OrderItemCollection;
use Eccube\Service\PurchaseFlow\ItemCollection;

/**
 * @ORM\Table(name="plg_eccube_payment_lite4_regular_order")
 * @ORM\Entity(repositoryClass="Plugin\EccubePaymentLite4\Repository\RegularOrderRepository")
 */
class RegularOrder extends AbstractEntity
{
    use NameTrait;
    use PointTrait;

    /**
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="pre_order_id", type="string", length=255, nullable=true)
     */
    private $pre_order_id;

    /**
     * @ORM\Column(name="order_no", type="string", length=255, nullable=true)
     */
    private $order_no;

    /**
     * @ORM\Column(name="message", type="string", length=4000, nullable=true)
     */
    private $message;

    /**
     * @ORM\Column(name="name01", type="string", length=255)
     */
    private $name01;

    /**
     * @ORM\Column(name="name02", type="string", length=255)
     */
    private $name02;

    /**
     * @ORM\Column(name="kana01", type="string", length=255, nullable=true)
     */
    private $kana01;

    /**
     * @ORM\Column(name="kana02", type="string", length=255, nullable=true)
     */
    private $kana02;

    /**
     * @ORM\Column(name="company_name", type="string", length=255, nullable=true)
     */
    private $company_name;

    /**
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(name="phone_number", type="string", length=14, nullable=true)
     */
    private $phone_number;

    /**
     * @ORM\Column(name="postal_code", type="string", length=8, nullable=true)
     */
    private $postal_code;

    /**
     * @ORM\Column(name="addr01", type="string", length=255, nullable=true)
     */
    private $addr01;

    /**
     * @ORM\Column(name="addr02", type="string", length=255, nullable=true)
     */
    private $addr02;

    /**
     * @ORM\Column(name="birth", type="datetimetz", nullable=true)
     */
    private $birth;

    /**
     * @ORM\Column(name="subtotal", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     */
    private $subtotal = 0;

    /**
     * @ORM\Column(name="discount", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     */
    private $discount = 0;

    /**
     * @ORM\Column(name="delivery_fee_total", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     */
    private $delivery_fee_total = 0;

    /**
     * @ORM\Column(name="charge", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     */
    private $charge = 0;

    /**
     * @ORM\Column(name="tax", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     */
    private $tax = 0;

    /**
     * @ORM\Column(name="total", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     */
    private $total = 0;

    /**
     * @ORM\Column(name="payment_total", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     */
    private $payment_total = 0;

    /**
     * @ORM\Column(name="payment_method", type="string", length=255, nullable=true)
     */
    private $payment_method;

    /**
     * @ORM\Column(name="note", type="string", length=4000, nullable=true)
     */
    private $note;

    /**
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @ORM\Column(name="update_date", type="datetimetz")
     */
    private $update_date;

    /**
     * @ORM\Column(name="order_date", type="datetimetz", nullable=true)
     */
    private $order_date;

    /**
     * @ORM\Column(name="payment_date", type="datetimetz", nullable=true)
     */
    private $payment_date;

    /**
     * @ORM\Column(name="currency_code", type="string", nullable=true)
     */
    private $currency_code;

    /**
     * 注文完了画面に表示するメッセージ
     *
     * プラグインから注文完了時にメッセージを表示したい場合, このフィールドにセットすることで, 注文完了画面で表示されます。
     * 複数のプラグインから利用されるため, appendCompleteMessage()で追加してください.
     * 表示する際にHTMLは利用可能です。
     *
     * @ORM\Column(name="complete_message", type="text", nullable=true)
     */
    private $complete_message;

    /**
     * 注文完了メールに表示するメッセージ
     *
     * プラグインから注文完了メールにメッセージを表示したい場合, このフィールドにセットすることで, 注文完了メールで表示されます。
     * 複数のプラグインから利用されるため, appendCompleteMailMessage()で追加してください.
     *
     * @ORM\Column(name="complete_mail_message", type="text", nullable=true)
     */
    private $complete_mail_message;

    /**
     * @ORM\OneToMany(targetEntity="Plugin\EccubePaymentLite4\Entity\RegularOrderItem", mappedBy="RegularOrder", cascade={"persist","remove"})
     */
    private $RegularOrderItems;

    /**
     * @ORM\OneToMany(targetEntity="Plugin\EccubePaymentLite4\Entity\RegularShipping", mappedBy="RegularOrder", cascade={"persist","remove"})
     */
    private $RegularShippings;

    /**
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     */
    private $Customer;

    /**
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\Country")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="id")
     */
    private $Country;

    /**
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\Pref")
     * @ORM\JoinColumn(name="pref_id", referencedColumnName="id")
     */
    private $Pref;

    /**
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\Sex")
     * @ORM\JoinColumn(name="sex_id", referencedColumnName="id")
     */
    private $Sex;

    /**
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\Job")
     * @ORM\JoinColumn(name="job_id", referencedColumnName="id")
     */
    private $Job;

    /**
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Payment")
     * @ORM\JoinColumn(name="payment_id", referencedColumnName="id")
     */
    private $Payment;

    /**
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\DeviceType")
     * @ORM\JoinColumn(name="device_type_id", referencedColumnName="id")
     */
    private $DeviceType;

    /**
     * OrderStatusより先にプロパティを定義しておかないとセットされなくなる
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\CustomerOrderStatus")
     * @ORM\JoinColumn(name="order_status_id", referencedColumnName="id")
     */
    private $CustomerOrderStatus;

    /**
     * OrderStatusより先にプロパティを定義しておかないとセットされなくなる
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\OrderStatusColor")
     * @ORM\JoinColumn(name="order_status_id", referencedColumnName="id")
     */
    private $OrderStatusColor;

    /**
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\OrderStatus")
     * @ORM\JoinColumn(name="order_status_id", referencedColumnName="id")
     */
    private $OrderStatus;

    /**
     * @ORM\Column(name="trans_code", type="text", nullable=true)
     */
    private $trans_code;

    /**
     * @ORM\Column(name="regular_order_count", type="integer", length=6, nullable=true, options={"default":1})
     */
    private $regular_order_count;

    /**
     * @ORM\ManyToOne(targetEntity="Plugin\EccubePaymentLite4\Entity\RegularStatus")
     * @ORM\JoinColumn(name="regular_status_id", referencedColumnName="id")
     */
    private $RegularStatus;

    /**
     * @ORM\ManyToOne(targetEntity="Plugin\EccubePaymentLite4\Entity\RegularCycle")
     * @ORM\JoinColumn(name="regular_cycle_id", referencedColumnName="id")
     */
    private $RegularCycle;
    /**
     * @ORM\OneToMany(targetEntity="Eccube\Entity\Order", mappedBy="RegularOrder")
     */
    private $Orders;

    /**
     * @ORM\Column(name="regular_skip_flag", type="integer", length=6, nullable=true, options={"default":0})
     */
    private $regular_skip_flag;

    /**
     * @ORM\Column(name="regular_stop_date", type="datetimetz", nullable=true)
     */
    private $regular_stop_date;

    public function __construct()
    {
        $this->RegularOrderItems = new ArrayCollection();
        $this->RegularShippings = new ArrayCollection();
        $this->Orders = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setPreOrderId($preOrderId = null)
    {
        $this->pre_order_id = $preOrderId;

        return $this;
    }

    public function getPreOrderId()
    {
        return $this->pre_order_id;
    }

    public function setOrderNo($orderNo = null)
    {
        $this->order_no = $orderNo;

        return $this;
    }

    public function getOrderNo()
    {
        return $this->order_no;
    }

    public function setMessage($message = null)
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setName01($name01 = null)
    {
        $this->name01 = $name01;

        return $this;
    }

    public function getName01()
    {
        return $this->name01;
    }

    public function setName02($name02 = null)
    {
        $this->name02 = $name02;

        return $this;
    }

    public function getName02()
    {
        return $this->name02;
    }

    public function setKana01($kana01 = null)
    {
        $this->kana01 = $kana01;

        return $this;
    }

    public function getKana01()
    {
        return $this->kana01;
    }

    public function setKana02($kana02 = null)
    {
        $this->kana02 = $kana02;

        return $this;
    }

    public function getKana02()
    {
        return $this->kana02;
    }

    public function setCompanyName($companyName = null)
    {
        $this->company_name = $companyName;

        return $this;
    }

    public function getCompanyName()
    {
        return $this->company_name;
    }

    public function setEmail($email = null)
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setPhoneNumber($phone_number = null)
    {
        $this->phone_number = $phone_number;

        return $this;
    }

    public function getPhoneNumber()
    {
        return $this->phone_number;
    }

    public function setPostalCode($postal_code = null)
    {
        $this->postal_code = $postal_code;

        return $this;
    }

    public function getPostalCode()
    {
        return $this->postal_code;
    }

    public function setAddr01($addr01 = null)
    {
        $this->addr01 = $addr01;

        return $this;
    }

    public function getAddr01()
    {
        return $this->addr01;
    }

    public function setAddr02($addr02 = null)
    {
        $this->addr02 = $addr02;

        return $this;
    }

    public function getAddr02()
    {
        return $this->addr02;
    }

    public function setBirth($birth = null)
    {
        $this->birth = $birth;

        return $this;
    }

    public function getBirth()
    {
        return $this->birth;
    }

    public function setSubtotal($subtotal)
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    public function getSubtotal()
    {
        return $this->subtotal;
    }

    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    public function getDiscount()
    {
        return $this->discount;
    }

    public function setDeliveryFeeTotal($deliveryFeeTotal)
    {
        $this->delivery_fee_total = $deliveryFeeTotal;

        return $this;
    }

    public function getDeliveryFeeTotal()
    {
        return $this->delivery_fee_total;
    }

    public function setCharge($charge)
    {
        $this->charge = $charge;

        return $this;
    }

    public function getCharge()
    {
        return $this->charge;
    }

    public function setTax($tax)
    {
        $this->tax = $tax;

        return $this;
    }

    public function getTax()
    {
        return $this->tax;
    }

    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function setPaymentTotal($paymentTotal)
    {
        $this->payment_total = $paymentTotal;

        return $this;
    }

    public function getPaymentTotal()
    {
        return $this->payment_total;
    }

    public function setPaymentMethod($paymentMethod = null)
    {
        $this->payment_method = $paymentMethod;

        return $this;
    }

    public function getPaymentMethod()
    {
        return $this->payment_method;
    }

    public function setNote($note = null)
    {
        $this->note = $note;

        return $this;
    }

    public function getNote()
    {
        return $this->note;
    }

    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    public function getCreateDate()
    {
        return $this->create_date;
    }

    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    public function getUpdateDate()
    {
        return $this->update_date;
    }

    public function setOrderDate($orderDate = null)
    {
        $this->order_date = $orderDate;

        return $this;
    }

    public function getOrderDate()
    {
        return $this->order_date;
    }

    public function setPaymentDate($paymentDate = null)
    {
        $this->payment_date = $paymentDate;

        return $this;
    }

    public function getPaymentDate()
    {
        return $this->payment_date;
    }

    public function getCurrencyCode()
    {
        return $this->currency_code;
    }

    public function setCurrencyCode($currencyCode = null)
    {
        $this->currency_code = $currencyCode;

        return $this;
    }

    public function getCompleteMessage()
    {
        return $this->complete_message;
    }

    public function setCompleteMessage($complete_message = null)
    {
        $this->complete_message = $complete_message;

        return $this;
    }

    public function getCompleteMailMessage()
    {
        return $this->complete_mail_message;
    }

    public function setCompleteMailMessage($complete_mail_message = null)
    {
        $this->complete_mail_message = $complete_mail_message;

        return $this;
    }

    public function setCustomer(Customer $customer = null)
    {
        $this->Customer = $customer;

        return $this;
    }

    public function getCustomer()
    {
        return $this->Customer;
    }

    public function setCountry(Country $country = null)
    {
        $this->Country = $country;

        return $this;
    }

    public function getCountry()
    {
        return $this->Country;
    }

    public function setPref(Pref $pref = null)
    {
        $this->Pref = $pref;

        return $this;
    }

    public function getPref()
    {
        return $this->Pref;
    }

    public function setSex(Sex $sex = null)
    {
        $this->Sex = $sex;

        return $this;
    }

    public function getSex()
    {
        return $this->Sex;
    }

    public function setJob(Job $job = null)
    {
        $this->Job = $job;

        return $this;
    }

    public function getJob()
    {
        return $this->Job;
    }

    public function setPayment(Payment $payment = null)
    {
        $this->Payment = $payment;

        return $this;
    }

    public function getPayment()
    {
        return $this->Payment;
    }

    public function setDeviceType(DeviceType $deviceType = null)
    {
        $this->DeviceType = $deviceType;

        return $this;
    }

    public function getDeviceType()
    {
        return $this->DeviceType;
    }

    public function setCustomerOrderStatus(CustomerOrderStatus $customerOrderStatus = null)
    {
        $this->CustomerOrderStatus = $customerOrderStatus;

        return $this;
    }

    public function getCustomerOrderStatus()
    {
        return $this->CustomerOrderStatus;
    }

    public function setOrderStatusColor(OrderStatusColor $orderStatusColor = null)
    {
        $this->OrderStatusColor = $orderStatusColor;

        return $this;
    }

    public function getOrderStatusColor()
    {
        return $this->OrderStatusColor;
    }

    public function setOrderStatus(OrderStatus $orderStatus = null)
    {
        $this->OrderStatus = $orderStatus;

        return $this;
    }

    public function getOrderStatus()
    {
        return $this->OrderStatus;
    }

    public function addRegularOrderItem(RegularOrderItem $RegularOrderItem)
    {
        $this->RegularOrderItems[] = $RegularOrderItem;

        return $this;
    }

    public function removeRegularOrderItem(RegularOrderItem $RegularOrderItem)
    {
        return $this->RegularOrderItems->removeElement($RegularOrderItem);
    }

    public function getRegularOrderItems()
    {
        return $this->RegularOrderItems;
    }

    public function addRegularShipping(RegularShipping $RegularShipping)
    {
        $this->RegularShippings[] = $RegularShipping;

        return $this;
    }

    public function removeRegularShipping(RegularShipping $RegularShipping)
    {
        return $this->RegularShippings->removeElement($RegularShipping);
    }

    public function getRegularShippings()
    {
        $criteria = Criteria::create()
            ->orderBy(['name01' => Criteria::ASC, 'name02' => Criteria::ASC, 'id' => Criteria::ASC]);

        return $this->RegularShippings->matching($criteria);
    }

    public function setTransCode($transCode)
    {
        $this->trans_code = $transCode;

        return $this;
    }

    public function getTransCode()
    {
        return $this->trans_code;
    }

    public function setRegularOrderCount($regularOrderCount)
    {
        $this->regular_order_count = $regularOrderCount;

        return $this;
    }

    public function getRegularOrderCount()
    {
        return $this->regular_order_count;
    }

    public function setRegularSkipFlag($regularSkipFlag)
    {
        $this->regular_skip_flag = $regularSkipFlag;

        return $this;
    }

    public function getRegularSkipFlag()
    {
        return $this->regular_skip_flag;
    }

    public function setRegularStatus(RegularStatus $regularStatus)
    {
        $this->RegularStatus = $regularStatus;

        return $this;
    }

    public function getRegularStatus(): RegularStatus
    {
        return $this->RegularStatus;
    }

    public function getItems()
    {
        return (new ItemCollection($this->getRegularOrderItems()))->sort();
    }

    public function getQuantity()
    {
        $quantity = 0;
        foreach ($this->getItems() as $item) {
            $quantity += $item->getQuantity();
        }

        return $quantity;
    }

    public function setRegularCycle(RegularCycle $regularCycle = null)
    {
        $this->RegularCycle = $regularCycle;

        return $this;
    }

    public function getRegularCycle()
    {
        return $this->RegularCycle;
    }

    /**
     * @return array
     */
    public function getRegularProductOrderItems()
    {
        $sio = new OrderItemCollection($this->RegularOrderItems->toArray());

        return array_values($sio->getProductClasses()->toArray());
    }

    /**
     * 複数配送かどうかの判定を行う.
     *
     * @return boolean
     */
    public function isMultiple()
    {
        $RegularShippings = [];

        foreach ($this->getRegularOrderItems() as $Item) {
            if ($RegularShipping = $Item->getRegularShipping()) {
                $id = $RegularShipping->getId();
                if (isset($Shippings[$id])) {
                    continue;
                }

                $RegularShippings[$id] = $RegularShipping;
            }
        }

        return count($RegularShippings) > 1;
    }

    /**
     * 課税対象の明細を返す.
     *
     * @return array
     */
    public function getTaxableItems()
    {
        $Items = [];

        foreach ($this->RegularOrderItems as $Item) {
            if ($Item->getTaxType()->getId() == TaxType::TAXATION) {
                $Items[] = $Item;
            }
        }

        return $Items;
    }

    /**
     * 課税対象の明細の合計金額を返す.
     * 商品合計 + 送料 + 手数料 + 値引き(課税).
     */
    public function getTaxableTotal()
    {
        $total = 0;

        foreach ($this->getTaxableItems() as $Item) {
            $total += $Item->getTotalPrice();
        }

        return $total;
    }

    /**
     * 課税対象の明細の合計金額を、税率ごとに集計する.
     *
     * @return array
     */
    public function getTaxableTotalByTaxRate()
    {
        $total = [];

        foreach ($this->getTaxableItems() as $Item) {
            $totalPrice = $Item->getTotalPrice();
            $taxRate = $Item->getTaxRate();
            $total[$taxRate] = isset($total[$taxRate])
                    ? $total[$taxRate] + $totalPrice
                    : $totalPrice;
        }

        krsort($total);

        return $total;
    }

    /**
     * 課税対象の値引き明細を返す.
     *
     * @return array
     */
    public function getTaxableDiscountItems()
    {
        return array_filter($this->getTaxableItems(), function (RegularOrderItem $Item) {
            return $Item->isDiscount();
        });
    }

    /**
     * 課税対象の値引き金額合計を返す.
     *
     * @return mixed
     */
    public function getTaxableDiscount()
    {
        return array_reduce($this->getTaxableDiscountItems(), function ($sum, RegularOrderItem $Item) {
            return $sum += $Item->getTotalPrice();
        }, 0);
    }

    /**
     * 非課税・不課税の値引き明細を返す.
     *
     * @return array
     */
    public function getTaxFreeDiscountItems()
    {
        return array_filter($this->RegularOrderItems->toArray(), function (RegularOrderItem $Item) {
            return $Item->isPoint() || ($Item->isDiscount() && $Item->getTaxType()->getId() != TaxType::TAXATION);
        });
    }

    public function getMergedRegularProductOrderItems()
    {
        $ProductOrderItems = $this->getRegularProductOrderItems();
        $orderItemArray = [];
        /** @var RegularOrderItem $ProductOrderItem */
        foreach ($ProductOrderItems as $ProductOrderItem) {
            $productClassId = $ProductOrderItem->getProductClass()->getId();
            if (array_key_exists($productClassId, $orderItemArray)) {
                // 同じ規格の商品がある場合は個数をまとめる
                $RegularOrderItem = $orderItemArray[$productClassId];
                $quantity = $RegularOrderItem->getQuantity() + $ProductOrderItem->getQuantity();
                $RegularOrderItem->setQuantity($quantity);
            } else {
                // 新規規格の商品は新しく追加する
                $RegularOrderItem = new RegularOrderItem();
                $RegularOrderItem->copyProperties($ProductOrderItem, ['id']);
                $orderItemArray[$productClassId] = $RegularOrderItem;
            }
        }

        return array_values($orderItemArray);
    }

    public function getOrders()
    {
        return $this->Orders;
    }

    public function setOrders(ArrayCollection $Orders): self
    {
        $this->Orders = $Orders;

        return $this;
    }

    public function getFirstOrder()
    {
        $orderBy = (Criteria::create())->orderBy([
            'create_date' => Criteria::ASC,
        ]);

        foreach ($this->Orders->matching($orderBy) as $Order) {
            /** @var Order $Order */
            if ($Order->getOrderStatus()->getId() !== OrderStatus::PROCESSING &&
                $Order->getOrderStatus()->getId() !== OrderStatus::PENDING) {
                return $Order->toArray();
            }
        }

        return [];
    }

    public function getLastOrder()
    {
        $orderBy = (Criteria::create())->orderBy([
            'create_date' => Criteria::DESC,
        ]);
        $this->Orders->matching($orderBy);
        foreach ($this->Orders->matching($orderBy) as $Order) {
            /** @var Order $Order */
            if ($Order->getOrderStatus()->getId() !== OrderStatus::PROCESSING &&
                $Order->getOrderStatus()->getId() !== OrderStatus::PENDING) {
                return $Order->toArray();
            }
        }

        return [];
    }

    public function getRegularStopDate()
    {
        return $this->regular_stop_date;
    }

    public function setRegularStopDate($regular_stop_date): self
    {
        $this->regular_stop_date = $regular_stop_date;

        return $this;
    }
}
