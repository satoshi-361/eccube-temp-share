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
     * @var \Eccube\Entity\Customer
     *
     * @ORM\ManyToOne(targetEntity="\Eccube\Entity\Customer", inversedBy="Blogs")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     * })
     */
    private $Customer;
    
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
}