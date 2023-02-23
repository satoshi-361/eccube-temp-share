<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
use Symfony\Component\Validator\Constraints as Assert;

/**
  * @EntityExtension("Eccube\Entity\MailHistory")
 */
trait MailHistoryTrait
{
    /**
     * @var integer
     * @ORM\Column(type="integer", nullable=true)
     */
    private $type = null;
    
    /**
     * Get type
     * 
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param  integer  $type
     *
     * @return this
     */
    public function setType($type)
    {
        $this->type = $type;
        
        return $this;
    }
}