<?php

namespace Plugin\CMBlogPro\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BlogCategory
 *
 * @ORM\Table(name="plg_blog_blog_category2")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\CMBlogPro\Repository\BlogCategoryRepository")
 */
class BlogCategory
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
     * @ORM\Column(name="category_id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $category_id;

    /**
     * @var \Plugin\CMBlogPro\Entity\Blog
     *
     * @ORM\ManyToOne(targetEntity="Plugin\CMBlogPro\Entity\Blog", inversedBy="BlogCategories")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="blog_id", referencedColumnName="id")
     * })
     */
    private $Blog;

    /**
     * @var \Plugin\CMBlogPro\Entity\Category
     *
     * @ORM\ManyToOne(targetEntity="Plugin\CMBlogPro\Entity\Category", inversedBy="BlogCategories")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     * })
     */
    private $Category;

    /**
     * Set blogId.
     *
     * @param int $blogId
     *
     * @return BlogCategory
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
     * Set categoryId.
     *
     * @param int $categoryId
     *
     * @return BlogCategory
     */
    public function setCategoryId($categoryId)
    {
        $this->category_id = $categoryId;

        return $this;
    }

    /**
     * Get categoryId.
     *
     * @return int
     */
    public function getCategoryId()
    {
        return $this->category_id;
    }

    /**
     * Set blog.
     *
     * @param \Plugin\CMBlogPro\Entity\Blog|null $blog
     *
     * @return BlogCategory
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
     * Set category.
     *
     * @param \Plugin\CMBlogPro\Entity\Category|null $category
     *
     * @return BlogCategory
     */
    public function setCategory(\Plugin\CMBlogPro\Entity\Category $category = null)
    {
        $this->Category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return \Plugin\CMBlogPro\Category|null
     */
    public function getCategory()
    {
        return $this->Category;
    }
}
