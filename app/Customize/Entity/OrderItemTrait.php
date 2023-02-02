<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
use Symfony\Component\Validator\Constraints as Assert;

/**
  * @EntityExtension("Eccube\Entity\OrderItem")
 */
trait OrderItemTrait
{
    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    private $affiliater = null;
    
    /**
     * Get affiliater
     * 
     * @return integer
     */
    public function getAffiliater()
    {
        return $this->affiliater;
    }

    /**
     * @param  integer  $affiliater
     *
     * @return this
     */
    public function setAffiliater($affiliater)
    {
        $this->affiliater = $affiliater;
        
        return $this;
    }
}