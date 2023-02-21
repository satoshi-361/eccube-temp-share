<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
use Symfony\Component\Validator\Constraints as Assert;

/**
  * @EntityExtension("Eccube\Entity\BaseInfo")
 */
trait BaseInfoTrait
{
    /**
     * @var string
     * 
     * @ORM\Column(name="fee", type="decimal", precision=10, scale=2, options={"default":18.5})
     */
    private $fee = '18.5';

    /**
     * Get fee
     * 
     * @return string
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * @param  string  $fee
     *
     * @return this
     */
    public function setFee($fee)
    {
        $this->fee = $fee;
        
        return $this;
    }
}