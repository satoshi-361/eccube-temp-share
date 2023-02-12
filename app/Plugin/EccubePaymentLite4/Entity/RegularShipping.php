<?php

namespace Plugin\EccubePaymentLite4\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Eccube\Entity\Delivery;
use Eccube\Entity\Master\Country;
use Eccube\Entity\Master\Pref;
use Eccube\Entity\Member;
use Eccube\Service\Calculator\OrderItemCollection;
use Eccube\Service\PurchaseFlow\ItemCollection;

/**
 * @ORM\Table(name="plg_eccube_payment_lite4_regular_shipping")
 * @ORM\Entity(repositoryClass="Plugin\EccubePaymentLite4\Repository\RegularShippingRepository")
 */
class RegularShipping extends AbstractEntity
{
    const CARD_CHANGE_REQUEST_MAIL_UNSENT = 1;
    const CARD_CHANGE_REQUEST_MAIL_SENT = 2;
    /**
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

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
     * @ORM\Column(name="delivery_name", type="string", length=255, nullable=true)
     */
    private $shipping_delivery_name;

    /**
     * @ORM\Column(name="time_id", type="integer", options={"unsigned":true}, nullable=true)
     */
    private $time_id;

    /**
     * @ORM\Column(name="delivery_time", type="string", length=255, nullable=true)
     */
    private $shipping_delivery_time;

    /**
     * @ORM\Column(name="delivery_date", type="datetimetz", nullable=true)
     */
    private $shipping_delivery_date;

    /**
     * @ORM\Column(name="shipping_date", type="datetimetz", nullable=true)
     */
    private $shipping_date;

    /**
     * @ORM\Column(name="next_delivery_date", type="datetimetz", nullable=true)
     */
    private $next_delivery_date;

    /**
     * @ORM\Column(name="tracking_number", type="string", length=255, nullable=true)
     */
    private $tracking_number;

    /**
     * @ORM\Column(name="note", type="string", length=4000, nullable=true)
     */
    private $note;

    /**
     * @ORM\Column(name="sort_no", type="smallint", nullable=true, options={"unsigned":true})
     */
    private $sort_no;

    /**
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @ORM\Column(name="update_date", type="datetimetz")
     */
    private $update_date;

    /**
     * @ORM\Column(name="mail_send_date", type="datetimetz", nullable=true)
     */
    private $mail_send_date;

    /**
     * @ORM\ManyToOne(targetEntity="Plugin\EccubePaymentLite4\Entity\RegularOrder", inversedBy="RegularShippings", cascade={"persist"})
     * @ORM\JoinColumn(name="regular_order_id", referencedColumnName="id")
     */
    private $RegularOrder;

    /**
     * @ORM\OneToMany(targetEntity="Plugin\EccubePaymentLite4\Entity\RegularOrderItem", mappedBy="RegularShipping", cascade={"persist"})
     */
    private $RegularOrderItems;

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
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Delivery")
     * @ORM\JoinColumn(name="delivery_id", referencedColumnName="id")
     */
    private $Delivery;

    /**
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Member")
     * @ORM\JoinColumn(name="creator_id", referencedColumnName="id")
     */
    private $Creator;

    public function __construct()
    {
        $this->RegularOrderItems = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName01($name01)
    {
        $this->name01 = $name01;

        return $this;
    }

    public function getName01()
    {
        return $this->name01;
    }

    public function setName02($name02)
    {
        $this->name02 = $name02;

        return $this;
    }

    public function getName02()
    {
        return $this->name02;
    }

    public function setKana01($kana01)
    {
        $this->kana01 = $kana01;

        return $this;
    }

    public function getKana01()
    {
        return $this->kana01;
    }

    public function setKana02($kana02)
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

    public function setShippingDeliveryName($shippingDeliveryName = null)
    {
        $this->shipping_delivery_name = $shippingDeliveryName;

        return $this;
    }

    public function getShippingDeliveryName()
    {
        return $this->shipping_delivery_name;
    }

    public function setShippingDeliveryTime($shippingDeliveryTime = null)
    {
        $this->shipping_delivery_time = $shippingDeliveryTime;

        return $this;
    }

    public function getShippingDeliveryTime()
    {
        return $this->shipping_delivery_time;
    }

    public function setShippingDeliveryDate($shippingDeliveryDate = null)
    {
        $this->shipping_delivery_date = $shippingDeliveryDate;

        return $this;
    }

    public function getShippingDeliveryDate()
    {
        return $this->shipping_delivery_date;
    }

    public function setShippingDate($shippingDate = null)
    {
        $this->shipping_date = $shippingDate;

        return $this;
    }

    public function getShippingDate()
    {
        return $this->shipping_date;
    }

    public function setNextDeliveryDate($next_delivery_date = null)
    {
        $this->next_delivery_date = $next_delivery_date;

        return $this;
    }

    public function getNextDeliveryDate()
    {
        return $this->next_delivery_date;
    }

    public function setSortNo($sortNo = null)
    {
        $this->sort_no = $sortNo;

        return $this;
    }

    public function getSortNo()
    {
        return $this->sort_no;
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

    public function setMailSendDate($mailSendDate)
    {
        $this->mail_send_date = $mailSendDate;

        return $this;
    }

    public function getMailSendDate()
    {
        return $this->mail_send_date;
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
        return (new ItemCollection($this->RegularOrderItems))->sort();
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

    public function setDelivery($delivery = null)
    {
        $this->Delivery = $delivery;

        return $this;
    }

    public function getDelivery(): Delivery
    {
        return $this->Delivery;
    }

    public function setRegularOrder(RegularOrder $RegularOrder)
    {
        $this->RegularOrder = $RegularOrder;

        return $this;
    }

    public function getRegularOrder()
    {
        return $this->RegularOrder;
    }

    public function setTrackingNumber($trackingNumber)
    {
        $this->tracking_number = $trackingNumber;

        return $this;
    }

    public function getTrackingNumber()
    {
        return $this->tracking_number;
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

    public function setTimeId($timeId)
    {
        $this->time_id = $timeId;

        return $this;
    }

    public function getTimeId()
    {
        return $this->time_id;
    }

    public function setCreator(Member $creator = null)
    {
        $this->Creator = $creator;

        return $this;
    }

    public function getCreator()
    {
        return $this->Creator;
    }

    public function getRegularProductOrderItems()
    {
        $sio = new OrderItemCollection($this->RegularOrderItems->toArray());

        return $sio->getProductClasses()->toArray();
    }
}
