<?php

namespace Plugin\CustomerReview4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Eccube\Entity\Product;
use Eccube\Entity\Customer;


/**
 * CustomerReviewList
 *
 * @ORM\Table(name="plg_customer_review_list", indexes={
 *     @ORM\Index(name="idx_create_date", columns={"product_id","create_date"}),
 *     @ORM\Index(name="idx_recommend_level", columns={"product_id","recommend_level"})
 * })
 * @ORM\Entity(repositoryClass="Plugin\CustomerReview4\Repository\CustomerReviewListRepository")
 */

class CustomerReviewList extends AbstractEntity
{
    // order by
    /**
     * 投稿日が新しい順
    **/
    const POST_NEWER = 1;

    /**
     * 投稿日が古い順
    **/
    const POST_OLDER = 2;

    /**
     * 評価が高い順
    **/
    const RECOMMEND_HIGHER = 3;

    /**
     * 評価が低い順
    **/
    const RECOMMEND_LOWER = 4;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     * })
     */
    private $Product;

    /**
     * @var Customer
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     * })
     */
    private $Customer;

    /**
     * @var Status
     *
     * @ORM\ManyToOne(targetEntity="Plugin\CustomerReview4\Entity\CustomerReviewStatus")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="status", referencedColumnName="id")
     * })
     */
    private $Status;

    /**
     * @var boolean
     *
     * @ORM\Column(name="purchase", type="boolean", options={"default":false})
     */
    private $purchase = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="grant_point", type="boolean", options={"default":false})
     */
    private $grant_point = false;

    /**
     * @var string
     *
     * @ORM\Column(name="reviewer_name", type="string", length=255)
     */
    private $reviewer_name;

    /**
     * @var int
     *
     * @ORM\Column(name="recommend_level", type="smallint", options={"unsigned":true})
     */
    private $recommend_level;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="text")
     */
    private $comment;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetimetz")
     */
    private $update_date;


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
     * @return Product
     */
    public function getProduct()
    {
        return $this->Product;
    }

    /**
     * @param Product $Product
     *
     * @return $this
     */
    public function setProduct(Product $Product)
    {
        $this->Product = $Product;

        return $this;
    }

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
     * @return CustomerReviewStatus
     */
    public function getStatus()
    {
        return $this->Status;
    }

    /**
     * @param CustomerReviewStatus $Status
     *
     * @return $this
     */
    public function setStatus(CustomerReviewStatus $Status)
    {
        $this->Status = $Status;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isPurchase()
    {
        return $this->purchase;
    }

    /**
     * @param boolean $purchase
     *
     * @return $this
     */
    public function setPurchase($purchase)
    {
        $this->purchase = $purchase;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isGrantPoint()
    {
        return $this->grant_point;
    }

    /**
     * @param boolean $grant_point
     *
     * @return $this
     */
    public function setGrantPoint($grant_point)
    {
        $this->grant_point = $grant_point;

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
     * @return ProductReview
     */
    public function setReviewerName($reviewer_name)
    {
        $this->reviewer_name = $reviewer_name;

        return $this;
    }

    /**
     * @return int
     */
    public function getRecommendLevel()
    {
        return $this->recommend_level;
    }

    /**
     * @param int $recommend_level
     *
     * @return ProductReview
     */
    public function setRecommendLevel($recommend_level)
    {
        $this->recommend_level = $recommend_level;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return ProductReview
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     *
     * @return ProductReview
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * @param \DateTime $createDate
     *
     * @return $this
     */
    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

    /**
     * @param \DateTime $updateDate
     *
     * @return $this
     */
    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }
}
