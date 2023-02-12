<?php

namespace Plugin\EccubePaymentLite4\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * @ORM\Table(name="plg_eccube_payment_lite4_regular_count_discount")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\EccubePaymentLite4\Repository\RegularDiscountRepository")
 */
class RegularDiscount extends AbstractEntity
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
     * @var int
     *
     * @ORM\Column(name="discount_id", type="integer", options={"unsigned":true})
     */
    private $discount_id;

    /**
     * @var int
     *
     * @ORM\Column(name="item_id", type="integer", options={"unsigned":true})
     */
    private $item_id;

    /**
     * @var int
     *
     * @ORM\Column(name="regular_count", type="integer", nullable=true)
     */
    private $regular_count;

    /**
     * @var int
     *
     * @ORM\Column(name="discount_rate", type="integer", nullable=true)
     */
    private $discount_rate;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="update_date", type="datetimetz")
     */
    private $update_date;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getDiscountId()
    {
        return $this->discount_id;
    }

    /**
     * Set DiscountId
     *
     * @param int $discount_id
     *
     * @return self
     */
    public function setDiscountId($discount_id = null)
    {
        $this->discount_id = $discount_id;

        return $this;
    }

    /**
     * Get ItemId
     *
     * @return int
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set ItemId
     *
     * @param int $item_id
     *
     * @return self
     */
    public function setItemId($item_id = null)
    {
        $this->item_id = $item_id;

        return $this;
    }

    /**
     * Get Regular Count
     *
     * @return int
     */
    public function getRegularCount()
    {
        return $this->regular_count;
    }

    /**
     * Set Regular Count
     *
     * @param int $regular_count
     *
     * @return self
     */
    public function setRegularCount($regular_count = null)
    {
        $this->regular_count = $regular_count;

        return $this;
    }

    /**
     * Get Discount Rate
     *
     * @return int
     */
    public function getDiscountRate()
    {
        return $this->discount_rate;
    }

    /**
     * Set Discount Rate
     *
     * @param int $discount_rate
     *
     * @return self
     */
    public function setDiscountRate($discount_rate = null)
    {
        $this->discount_rate = $discount_rate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * @param $create_date
     *
     * @return self
     */
    public function setCreateDate($create_date)
    {
        $this->create_date = $create_date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

    /**
     * @param $update_date
     *
     * @return self
     */
    public function setUpdateDate($update_date)
    {
        $this->update_date = $update_date;

        return $this;
    }

    public function __toString(): string
    {
        return $this->discount_id;
    }
}
