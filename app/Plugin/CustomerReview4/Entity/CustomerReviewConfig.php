<?php

namespace Plugin\CustomerReview4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Eccube\Entity\Master\CsvType;

/**
 * CustomerReviewConfig
 *
 * @ORM\Table(name="plg_customer_review_config")
 * @ORM\Entity(repositoryClass="Plugin\CustomerReview4\Repository\CustomerReviewConfigRepository")
 */

class CustomerReviewConfig extends AbstractEntity
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
     * @var \Eccube\Entity\Master\CsvType
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\CsvType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="csv_type_id", nullable=true, referencedColumnName="id")
     * })
     */
    private $CsvType;

    /**
     * @var int
     *
     * @ORM\Column(name="review_max", type="smallint", options={"unsigned":true, "default":20})
     */
    private $review_max;

    /**
     * @var int
     *
     * @ORM\Column(name="grant_point", type="smallint", options={"unsigned":true, "default":0})
     */
    private $grant_point;

    /**
     * @var boolean
     *
     * @ORM\Column(name="grant_point_purchase", type="boolean", options={"default":false})
     */
    private $grant_point_purchase = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="login_only", type="boolean", options={"default":false})
     */
    private $login_only = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="detail_in_review", type="boolean", options={"default":true})
     */
    private $detail_in_review = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="purchase_mark", type="boolean", options={"default":true})
     */
    private $purchase_mark = true;

    /**
     * @var string
     *
     * @ORM\Column(name="default_reviewer_name", type="string", length=255)
     */
    private $default_reviewer_name;


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get CsvType
     *
     * @return \Eccube\Entity\Master\CsvType
     */
    public function getCsvType()
    {
        return $this->CsvType;
    }

    /**
     * Set CsvType
     *
     * @param CsvType $CsvType
     *
     * @return $this
     */
    public function setCsvType(CsvType $CsvType = null)
    {
        $this->CsvType = $CsvType;

        return $this;
    }

    /**
     * @return int
     */
    public function getReviewMax()
    {
        return $this->review_max;
    }

    /**
     * @param int $max
     *
     * @return $this
     */
    public function setReviewMax($max)
    {
        $this->review_max = $max;

        return $this;
    }

    /**
     * @return int
     */
    public function getGrantPoint()
    {
        return $this->grant_point;
    }

    /**
     * @param int $point
     *
     * @return $this
     */
    public function setGrantPoint($point)
    {
        $this->grant_point = $point;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isGrantPointPurchase()
    {
        return $this->grant_point_purchase;
    }

    /**
     * @param boolean $grantPointPurchase
     *
     * @return $this
     */
    public function setGrantPointPurchase($grantPointPurchase)
    {
        $this->grant_point_purchase = $grantPointPurchase;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isLoginOnly()
    {
        return $this->login_only;
    }

    /**
     * @param boolean $loginOnly
     *
     * @return $this
     */
    public function setLoginOnly($loginOnly)
    {
        $this->login_only = $loginOnly;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isDetailInReview()
    {
        return $this->detail_in_review;
    }

    /**
     * @param boolean $detailInReview
     *
     * @return $this
     */
    public function setDetailInReview($detailInReview)
    {
        $this->detail_in_review = $detailInReview;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isPurchaseMark()
    {
        return $this->purchase_mark;
    }

    /**
     * @param boolean $purchaseMark
     *
     * @return $this
     */
    public function setPurchaseMark($purchaseMark)
    {
        $this->purchase_mark = $purchaseMark;

        return $this;
    }


    /**
     * @return string
     */
    public function getDefaultReviewerName()
    {
        return $this->default_reviewer_name;
    }

    /**
     * @param string $default_reviewer_name
     *
     * @return $this;
     */
    public function setDefaultReviewerName($default_reviewer_name)
    {
        $this->default_reviewer_name = $default_reviewer_name;

        return $this;
    }
}
