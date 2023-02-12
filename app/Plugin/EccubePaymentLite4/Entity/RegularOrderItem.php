<?php

namespace Plugin\EccubePaymentLite4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Eccube\Entity\ItemInterface;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\RoundingType;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Entity\Master\TaxType;
use Eccube\Entity\PointRateTrait;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;

/**
 * @ORM\Table(name="plg_eccube_payment_lite4_regular_order_item")
 * @ORM\Entity(repositoryClass="Plugin\EccubePaymentLite4\Repository\RegularOrderItemRepository")
 */
class RegularOrderItem extends AbstractEntity implements ItemInterface
{
    use PointRateTrait;

    /**
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="product_name", type="string", length=255)
     */
    private $product_name;

    /**
     * @ORM\Column(name="product_code", type="string", length=255, nullable=true)
     */
    private $product_code;

    /**
     * @ORM\Column(name="class_name1", type="string", length=255, nullable=true)
     */
    private $class_name1;

    /**
     * @ORM\Column(name="class_name2", type="string", length=255, nullable=true)
     */
    private $class_name2;

    /**
     * @ORM\Column(name="class_category_name1", type="string", length=255, nullable=true)
     */
    private $class_category_name1;

    /**
     * @ORM\Column(name="class_category_name2", type="string", length=255, nullable=true)
     */
    private $class_category_name2;

    /**
     * @ORM\Column(name="price", type="decimal", precision=12, scale=2, options={"default":0})
     */
    private $price = 0;

    /**
     * @ORM\Column(name="quantity", type="decimal", precision=10, scale=0, options={"default":0})
     */
    private $quantity = 0;

    /**
     * @ORM\Column(name="tax", type="decimal", precision=10, scale=0, options={"default":0})
     */
    private $tax = 0;

    /**
     * @ORM\Column(name="tax_rate", type="decimal", precision=10, scale=0, options={"unsigned":true,"default":0})
     */
    private $tax_rate = 0;

    /**
     * @ORM\Column(name="tax_adjust", type="decimal", precision=10, scale=0, options={"unsigned":true,"default":0})
     */
    private $tax_adjust = 0;

    /**
     * @ORM\Column(name="tax_rule_id", type="smallint", nullable=true, options={"unsigned":true})
     */
    private $tax_rule_id;

    /**
     * @ORM\Column(name="currency_code", type="string", nullable=true)
     */
    private $currency_code;

    /**
     * @ORM\Column(name="processor_name", type="string", nullable=true)
     */
    private $processor_name;

    /**
     * @ORM\ManyToOne(targetEntity="Plugin\EccubePaymentLite4\Entity\RegularOrder", inversedBy="RegularOrderItems")
     * @ORM\JoinColumn(name="regular_order_id", referencedColumnName="id")
     */
    private $RegularOrder;

    /**
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     */
    private $Product;

    /**
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\ProductClass")
     * @ORM\JoinColumn(name="product_class_id", referencedColumnName="id")
     */
    private $ProductClass;

    /**
     * @ORM\ManyToOne(targetEntity="Plugin\EccubePaymentLite4\Entity\RegularShipping", inversedBy="RegularOrderItems")
     * @ORM\JoinColumn(name="regular_shipping_id", referencedColumnName="id")
     */
    private $RegularShipping;

    /**
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\RoundingType")
     * @ORM\JoinColumn(name="rounding_type_id", referencedColumnName="id")
     */
    private $RoundingType;

    /**
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\TaxType")
     * @ORM\JoinColumn(name="tax_type_id", referencedColumnName="id")
     */
    private $TaxType;

    /**
     * @var TaxDisplayType
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\TaxDisplayType")
     * @ORM\JoinColumn(name="tax_display_type_id", referencedColumnName="id")
     */
    private $TaxDisplayType;

    /**
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\OrderItemType")
     * @ORM\JoinColumn(name="order_item_type_id", referencedColumnName="id")
     */
    private $OrderItemType;

    public function getId()
    {
        return $this->id;
    }

    public function setProductName($productName)
    {
        $this->product_name = $productName;

        return $this;
    }

    public function getProductName()
    {
        return $this->product_name;
    }

    public function setProductCode($productCode = null)
    {
        $this->product_code = $productCode;

        return $this;
    }

    public function getProductCode()
    {
        return $this->product_code;
    }

    public function setClassName1($className1 = null)
    {
        $this->class_name1 = $className1;

        return $this;
    }

    public function getClassName1()
    {
        return $this->class_name1;
    }

    public function setClassName2($className2 = null)
    {
        $this->class_name2 = $className2;

        return $this;
    }

    public function getClassName2()
    {
        return $this->class_name2;
    }

    public function setClassCategoryName1($classCategoryName1 = null)
    {
        $this->class_category_name1 = $classCategoryName1;

        return $this;
    }

    public function getClassCategoryName1()
    {
        return $this->class_category_name1;
    }

    public function setClassCategoryName2($classCategoryName2 = null)
    {
        $this->class_category_name2 = $classCategoryName2;

        return $this;
    }

    public function getClassCategoryName2()
    {
        return $this->class_category_name2;
    }

    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function getTax()
    {
        return $this->tax;
    }

    public function setTax($tax)
    {
        $this->tax = $tax;

        return $this;
    }

    public function setTaxRate($taxRate)
    {
        $this->tax_rate = $taxRate;

        return $this;
    }

    public function getTaxRate()
    {
        return $this->tax_rate;
    }

    public function setTaxAdjust($tax_adjust)
    {
        $this->tax_adjust = $tax_adjust;

        return $this;
    }

    public function getTaxAdjust()
    {
        return $this->tax_adjust;
    }

    public function setTaxRuleId($taxRuleId = null)
    {
        $this->tax_rule_id = $taxRuleId;

        return $this;
    }

    public function getTaxRuleId()
    {
        return $this->tax_rule_id;
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

    public function getProcessorName()
    {
        return $this->processor_name;
    }

    public function setProcessorName($processorName = null)
    {
        $this->processor_name = $processorName;

        return $this;
    }

    public function setRegularOrder(RegularOrder $RegularOrder = null)
    {
        $this->RegularOrder = $RegularOrder;

        return $this;
    }

    public function getRegularOrder()
    {
        return $this->RegularOrder;
    }

    public function setProduct(Product $product = null)
    {
        $this->Product = $product;

        return $this;
    }

    public function getProduct()
    {
        return $this->Product;
    }

    public function setProductClass(ProductClass $productClass = null)
    {
        $this->ProductClass = $productClass;

        return $this;
    }

    public function getProductClass()
    {
        return $this->ProductClass;
    }

    public function setRegularShipping(RegularShipping $RegularShipping = null)
    {
        $this->RegularShipping = $RegularShipping;

        return $this;
    }

    public function getRegularShipping()
    {
        return $this->RegularShipping;
    }

    public function getRoundingType()
    {
        return $this->RoundingType;
    }

    public function setRoundingType(RoundingType $RoundingType = null)
    {
        $this->RoundingType = $RoundingType;

        return $this;
    }

    public function setTaxType(TaxType $taxType = null)
    {
        $this->TaxType = $taxType;

        return $this;
    }

    public function getTaxType()
    {
        return $this->TaxType;
    }

    public function setTaxDisplayType(TaxDisplayType $taxDisplayType = null)
    {
        $this->TaxDisplayType = $taxDisplayType;

        return $this;
    }

    public function getTaxDisplayType()
    {
        return $this->TaxDisplayType;
    }

    public function setOrderItemType(OrderItemType $orderItemType = null)
    {
        $this->OrderItemType = $orderItemType;

        return $this;
    }

    public function getOrderItemType()
    {
        return $this->OrderItemType;
    }

    public function getPriceIncTax()
    {
        // 税表示区分が税込の場合は, priceに税込金額が入っている.
        if ($this->TaxDisplayType && $this->TaxDisplayType->getId() == TaxDisplayType::INCLUDED) {
            return $this->price;
        }

        return $this->price + $this->tax;
    }

    public function getTotalPrice()
    {
        return $this->getPriceIncTax() * $this->getQuantity();
    }

    public function getOrderItemTypeId()
    {
        if (is_object($this->getOrderItemType())) {
            return $this->getOrderItemType()->getId();
        }

        return null;
    }

    public function isProduct()
    {
        return $this->getOrderItemTypeId() === OrderItemType::PRODUCT;
    }

    public function isDeliveryFee()
    {
        return $this->getOrderItemTypeId() === OrderItemType::DELIVERY_FEE;
    }

    public function isCharge()
    {
        return $this->getOrderItemTypeId() === OrderItemType::CHARGE;
    }

    public function isDiscount()
    {
        return $this->getOrderItemTypeId() === OrderItemType::DISCOUNT;
    }

    public function isTax()
    {
        return $this->getOrderItemTypeId() === OrderItemType::TAX;
    }

    public function isPoint()
    {
        return $this->getOrderItemTypeId() === OrderItemType::POINT;
    }
}
