<?php

namespace Plugin\EccubePaymentLite4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * @ORM\Table(name="plg_eccube_payment_lite4_product_classes_regular_cycles")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\EccubePaymentLite4\Repository\ProductClassRegularCycleRepository")
 */
class ProductClassRegularCycle extends AbstractEntity
{
    public function __toString()
    {
        return (string) $this->getRegularCycle();
    }

    /**
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Plugin\EccubePaymentLite4\Entity\RegularCycle")
     * @ORM\JoinColumn(name="regular_cycle_id", referencedColumnName="id")
     */
    private $RegularCycle;

    /**
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\ProductClass")
     * @ORM\JoinColumn(name="product_class_id", referencedColumnName="id")
     */
    private $ProductClass;

    public function getId()
    {
        return $this->id;
    }

    public function getRegularCycle(): RegularCycle
    {
        return $this->RegularCycle;
    }

    public function setRegularCycle($RegularCycle)
    {
        $this->RegularCycle = $RegularCycle;

        return $this;
    }

    public function getProductClass()
    {
        return $this->ProductClass;
    }

    public function setProductClass($ProductClass)
    {
        $this->ProductClass = $ProductClass;

        return $this;
    }
}
