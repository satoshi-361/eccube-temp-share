<?php

namespace Plugin\CMBlogPro\Entity;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Category
 *
 * @ORM\Table(name="plg_blog_category2")
 * @ORM\Entity(repositoryClass="Plugin\CMBlogPro\Repository\CategoryRepository")
 * @UniqueEntity("name")
 */
class Category
{


    /**
     * カテゴリに紐づく商品があるかどうかを調べる.
     *
     * ProductCategoriesはExtra Lazyのため, lengthやcountで評価した際にはCOUNTのSQLが発行されるが,
     * COUNT自体が重いので, LIMIT 1で取得し存在チェックを行う.
     *
     * @see http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/working-with-associations.html#filtering-collections
     *
     * @return bool
     */
    public function hasBlogCategories()
    {
        $criteria = Criteria::create()
        ->orderBy(['category_id' => Criteria::ASC])
        ->setFirstResult(0)
        ->setMaxResults(1);

        return $this->BlogCategories->matching($criteria)->count() > 0;
    }

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, unique=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="class", type="string", length=100, nullable=true)
     */
    private $class;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\CMBlogPro\Entity\BlogCategory", mappedBy="Category", fetch="EXTRA_LAZY")
     */
    private $BlogCategories;

    /**
     * Get id.
     * 
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get Name
     * 
     * @return int
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set Name
     * 
     * @param string $value
     *
     * @return $this;
     */
    public function setName($value)
    {
        $this->name = $value;
        return $this;
    }

    /**
     * Get Class
     * 
     * @return int
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set Class
     * 
     * @param string $value
     *
     * @return $this;
     */
    public function setClass($value)
    {
        $this->class = $value;
        return $this;
    }

    /**
     * Add blogCategory.
     *
     * @param \Plugin\CMBlogPro\Entity\BlogCategory $blogCategory
     *
     * @return Category
     */
    public function addBlogCategory(\Plugin\CMBlogPro\Entity\BlogCategory $blogCategory)
    {
        $this->BlogCategories[] = $blogCategory;

        return $this;
    }

    /**
     * Remove blogCategory.
     *
     * @param \Plugin\CMBlogPro\Entity\BlogCategory $blogCategory
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeBlogCategory(\Plugin\CMBlogPro\Entity\BlogCategory $blogCategory)
    {
        return $this->BlogCategories->removeElement($blogCategory);
    }

    /**
     * Get blogCategories.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBlogCategories()
    {
        return $this->BlogCategories;
    }
}
