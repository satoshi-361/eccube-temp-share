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
}