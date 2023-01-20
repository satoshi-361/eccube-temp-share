<?php

namespace Plugin\CustomerReview4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;
use Eccube\Entity\Product;

/**
 * CustomerReviewTotal
 *
 * @ORM\Table(name="plg_customer_review_total", indexes={
 *     @ORM\Index(name="idx_recommend_avg", columns={"product_id","recommend_avg"}),
 *     @ORM\Index(name="idx_reviewer_num", columns={"product_id","reviewer_num"})
 * })
 * @ORM\Entity(repositoryClass="Plugin\CustomerReview4\Repository\CustomerReviewTotalRepository")
 */

class CustomerReviewTotal extends AbstractEntity
{
    /**
     * 評価の高い順でソート
    **/
    const SORT_RECOMMEND_HIGHER = 101;

    /**
     * 評価の多い順でソート
    **/
    const SORT_RECOMMEND_OUTNUMBER = 102;

    /**
     * @var \Eccube\Entity\Product
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     * })
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $Product;

    /**
     * @var int
     *
     * @ORM\Column(name="recommend1", type="integer", options={"unsigned":true, "default":0})
     */
    private $recommend1;

    /**
     * @var int
     *
     * @ORM\Column(name="recommend2", type="integer", options={"unsigned":true, "default":0})
     */
    private $recommend2;

    /**
     * @var int
     *
     * @ORM\Column(name="recommend3", type="integer", options={"unsigned":true, "default":0})
     */
    private $recommend3;

    /**
     * @var int
     *
     * @ORM\Column(name="recommend4", type="integer", options={"unsigned":true, "default":0})
     */
    private $recommend4;

    /**
     * @var int
     *
     * @ORM\Column(name="recommend5", type="integer", options={"unsigned":true, "default":0})
     */
    private $recommend5;

    /**
     * @var int
     *
     * @ORM\Column(name="recommend_avg", type="smallint", options={"unsigned":true, "default":0})
     */
    private $recommend_avg;

    /**
     * @var int
     *
     * @ORM\Column(name="reviewer_num", type="integer", options={"unsigned":true, "default":0})
     */
    private $reviewer_num;

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
     * @return int
     */
    public function getRecommend1()
    {
        return $this->recommend1;
    }

    /**
     * @param int $recommend
     *
     * @return $this
     */
    public function setRecommend1($recommend)
    {
        $this->recommend1 = $recommend;

        return $this;
    }

    /**
     * @return int
     */
    public function getRecommend2()
    {
        return $this->recommend2;
    }

    /**
     * @param int $recommend
     *
     * @return $this
     */
    public function setRecommend2($recommend)
    {
        $this->recommend2 = $recommend;

        return $this;
    }

    /**
     * @return int
     */
    public function getRecommend3()
    {
        return $this->recommend3;
    }

    /**
     * @param int $recommend
     *
     * @return $this
     */
    public function setRecommend3($recommend)
    {
        $this->recommend3 = $recommend;

        return $this;
    }

    /**
     * @return int
     */
    public function getRecommend4()
    {
        return $this->recommend4;
    }

    /**
     * @param int $recommend
     *
     * @return $this
     */
    public function setRecommend4($recommend)
    {
        $this->recommend4 = $recommend;

        return $this;
    }

    /**
     * @return int
     */
    public function getRecommend5()
    {
        return $this->recommend5;
    }

    /**
     * @param int $recommend
     *
     * @return $this
     */
    public function setRecommend5($recommend)
    {
        $this->recommend5 = $recommend;

        return $this;
    }

    /**
     * @return int
     */
    public function getRecommendAvg()
    {
        return $this->recommend_avg;
    }

    /**
     * @param int $recommend_avg
     *
     * @return $this
     */
    public function setRecommendAvg($recommend_avg)
    {
        $this->recommend_avg = $recommend_avg;

        return $this;
    }

    /**
     * @return int
     */
    public function getReviewerNum()
    {
        return $this->reviewer_num;
    }

    /**
     * @param int $reviewer_num
     *
     * @return $this
     */
    public function setReviewerNum($reviewer_num)
    {
        $this->reviewer_num = $reviewer_num;

        return $this;
    }
}
