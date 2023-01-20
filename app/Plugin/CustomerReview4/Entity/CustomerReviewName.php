<?php

namespace Plugin\CustomerReview4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Eccube\Entity\Customer;

/**
 * CustomerReviewName
 *
 * @ORM\Table(name="plg_customer_review_name")
 * @ORM\Entity(repositoryClass="Plugin\CustomerReview4\Repository\CustomerReviewNameRepository")
 */

class CustomerReviewName extends AbstractEntity
{
    /**
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     * })
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $Customer;

    /**
     * @var string
     *
     * @ORM\Column(name="reviewer_name", type="string", length=255)
     */
    private $reviewer_name;

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->Customer;
    }

    /**
     * @param Customer $Customer
     *
     * @return $this
     */
    public function setCustomer(Customer $Customer)
    {
        $this->Customer = $Customer;

        return $this;
    }

    /**
     * @return string
     */
    public function getReviewerName()
    {
        return $this->reviewer_name;
    }

    /**
     * @param string $reviewer_name
     *
     * @return $this;
     */
    public function setReviewerName($reviewer_name)
    {
        $this->reviewer_name = $reviewer_name;

        return $this;
    }
}
