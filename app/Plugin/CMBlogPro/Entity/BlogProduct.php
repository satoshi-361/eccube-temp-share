<?php

namespace Plugin\CMBlogPro\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BlogProduct
 *
 * @ORM\Table(name="plg_blog_blog_product2")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\CMBlogPro\Repository\BlogProductRepository")
 */
class BlogProduct
{
    /**
     * @var int
     *
     * @ORM\Column(name="blog_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $blog_id;

    /**
     * @var int
     *
     * @ORM\Column(name="product_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $product_id;

    /**
     * @var \Plugin\CMBlogPro\Entity\Blog
     *
     * @ORM\ManyToOne(targetEntity="Plugin\CMBlogPro\Entity\Blog", inversedBy="BlogProduct")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="blog_id", referencedColumnName="id")
     * })
     */
    private $Blog;

    /**
     * @var \Eccube\Entity\Product
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product", inversedBy="BlogProduct")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     * })
     */
    private $Product;

    /**
     * Set blogId.
     *
     * @param int $blogId
     *
     * @return BlogProduct
     */
    public function setBlogId($blogId)
    {
        $this->blog_id = $blogId;

        return $this;
    }

    /**
     * Get blogId.
     *
     * @return int
     */
    public function getBlogId()
    {
        return $this->blog_id;
    }

    /**
     * Set productId.
     *
     * @param int $productId
     *
     * @return BlogProduct
     */
    public function setProductId($productId)
    {
        $this->product_id = $productId;

        return $this;
    }

    /**
     * Get productId.
     *
     * @return int
     */
    public function getProductId()
    {
        return $this->product_id;
    }

    /**
     * Set blog.
     *
     * @param \Plugin\CMBlogPro\Entity\Blog|null $blog
     *
     * @return BlogProduct
     */
    public function setBlog(\Plugin\CMBlogPro\Entity\Blog $blog = null)
    {
        $this->Blog = $blog;

        return $this;
    }

    /**
     * Get blog.
     *
     * @return \Plugin\CMBlogPro\Entity\Blog|null
     */
    public function getBlog()
    {
        return $this->Blog;
    }

    /**
     * Set product.
     *
     * @param \Eccube\Entity\Product|null $product
     *
     * @return BlogProduct
     */
    public function setProduct(\Eccube\Entity\Product $product = null)
    {
        $this->Product = $product;

        return $this;
    }

    /**
     * Get product.
     *
     * @return \Eccube\Entity\Product|null
     */
    public function getProduct()
    {
        return $this->Product;
    }
}
