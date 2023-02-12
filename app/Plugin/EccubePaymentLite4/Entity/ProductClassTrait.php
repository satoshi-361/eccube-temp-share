<?php

namespace Plugin\EccubePaymentLite4\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\ProductClass")
 */
trait ProductClassTrait
{
    /**
     * @var ProductClassRegularCycle[]|ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Plugin\EccubePaymentLite4\Entity\ProductClassRegularCycle",
     *     mappedBy="ProductClass",
     *     cascade={"persist", "remove"}
     * )
     */
    private $ProductClassRegularCycle;

    /**
     * @ORM\ManyToOne(targetEntity="Plugin\EccubePaymentLite4\Entity\RegularDiscount")
     * @ORM\JoinColumn(name="regular_discount_id", referencedColumnName="id")
     */
    private $RegularDiscount;

    public function getRegularCycle()
    {
        $regularCycles = [];

        if (is_null($this->getProductClassRegularCycle())) {
            $this->ProductClassRegularCycle = new ArrayCollection();
        }

        foreach ($this->getProductClassRegularCycle() as $ProductClassRegularCycle) {
            /* @var ProductClassRegularCycle $ProductClassRegularCycle */
            $regularCycles[] = $ProductClassRegularCycle->getRegularCycle();
        }

        return $regularCycles;
    }

    public function addProductClassRegularCycle(ProductClassRegularCycle $ProductClassRegularCycle)
    {
        $this->ProductClassRegularCycle[] = $ProductClassRegularCycle;

        return $this;
    }

    public function removeProductClassRegularCycle(ProductClassRegularCycle $ProductClassRegularCycle)
    {
        return $this->ProductClassRegularCycle->removeElement($ProductClassRegularCycle);
    }

    public function getProductClassRegularCycle()
    {
        return $this->ProductClassRegularCycle;
    }

    public function getRegularDiscount()
    {
        return $this->RegularDiscount;
    }

    public function setRegularDiscount($RegularDiscount = null): self
    {
        $this->RegularDiscount = $RegularDiscount;

        return $this;
    }
}
