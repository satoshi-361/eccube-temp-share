<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
use Symfony\Component\Validator\Constraints as Assert;

/**
  * @EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
{
    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @Eccube\Annotation\FormAppend(
     *     auto_render=true,
     *     type="\Symfony\Component\Form\Extension\Core\Type\ChoiceType",
     *     options={
     *          "required": true,
     *          "label": false,
     *          "choices": {
     *              "0%" : 0,
     *              "10%" : 10,
     *              "20%" : 20,
     *              "30%" : 30,
     *              "40%" : 40,
     *              "50%" : 50,
     *              "60%" : 60,
     *              "70%" : 70,
     *              "80%" : 80,
     *          },
     *     })
     */
    private $affiliate_reward = 50;

    /**
     * @var \DateTime|null
     * 投稿者が発売日時を設定できる。
     *
     * @ORM\Column(name="launch_date", type="datetimetz", nullable=true)
     * @Eccube\Annotation\FormAppend(
     *     auto_render=false,
     *     type="\Symfony\Component\Form\Extension\Core\Type\DateTimeType",
     *     options={
     *          "required": false,
     *          "label": false,
     *          "input": "datetime",
     *          "widget": "single_text",
     *          "attr": {
     *              "class": "datetimepicker-input",
     *              "data-target": "#admin_product_launch_date",
     *              "data-toggle": "datetimepicker"
     *          }
     *     })
     */
    private $launch_date = null;

    /**
     * @var \DateTime|null
     * 管理者が「公開」を承認した日時
     *
     * @ORM\Column(name="approved_date", type="datetimetz", nullable=true)
     */
    private $approved_date = null;

    /**
     * @var \Eccube\Entity\Customer
     *
     * @ORM\ManyToOne(targetEntity="\Eccube\Entity\Customer", inversedBy="Blogs")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     * })
     */
    private $Customer;

    /**
     * @var \Customize\Entity\Master\ProductStatusColor
     *
     * @ORM\ManyToOne(targetEntity="Customize\Entity\Master\ProductStatusColor")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_status_id", referencedColumnName="id")
     * })
     */
    private $StatusColor;

    /**
     * Get affiliate_reward
     * 
     * @return integer
     */
    public function getAffiliateReward()
    {
        return $this->affiliate_reward;
    }

    /**
     * @param  integer  $affiliate_reward
     *
     * @return this
     */
    public function setAffiliateReward($affiliate_reward)
    {
        $this->affiliate_reward = $affiliate_reward;
        
        return $this;
    }
    
    /**
     * Set customer.
     *
     * @param \Eccube\Entity\Customer|null $customer
     *
     * @return Product
     */
    public function setCustomer(\Eccube\Entity\Customer $customer = null)
    {
        $this->Customer = $customer;

        return $this;
    }

    /**
     * Get customer.
     *
     * @return \Eccube\Entity\Customer|null
     */
    public function getCustomer()
    {
        return $this->Customer;
    }

    /**
     * Set StatusColor.
     *
     * @param \Customize\Entity\Master\ProductStatusColor|null $StatusColor
     *
     * @return Order
     */
    public function setStatusColor(\Customize\Entity\Master\ProductStatusColor $StatusColor = null)
    {
        $this->StatusColor = $StatusColor;

        return $this;
    }

    /**
     * Get StatusColor.
     *
     * @return \Customize\Entity\Master\ProductStatusColor|null
     */
    public function getStatusColor()
    {
        return $this->StatusColor;
    }

    /**
     * Set launchDate.
     *
     * @param \DateTime $launchDate
     *
     * @return Product
     */
    public function setLaunchDate($launchDate)
    {
        $this->launch_date = $launchDate;

        return $this;
    }

    /**
     * Get launchDate.
     *
     * @return \DateTime
     */
    public function getLaunchDate()
    {
        return $this->launch_date;
    }

    /**
     * Set approvedDate.
     *
     * @param \DateTime $approvedDate
     *
     * @return Product
     */
    public function setApprovedDate($approvedDate)
    {
        $this->approved_date = $approvedDate;

        return $this;
    }

    /**
     * Get approved_date.
     *
     * @return \DateTime
     */
    public function getApprovedDate()
    {
        return $this->approved_date;
    }
}