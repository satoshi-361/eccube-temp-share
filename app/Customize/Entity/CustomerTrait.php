<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
use Eccube\Entity\Product as Blog;

/**
  * @EntityExtension("Eccube\Entity\Customer")
 */
trait CustomerTrait
{
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Eccube\Entity\Product", mappedBy="Customer")
     */
    private $Blogs;

    /**
     * @var string|null
     *
     * @ORM\Column(name="nick_name", type="string", length=255)
     */
    private $nick_name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var string|null
     *
     * @ORM\Column(name="paypal_email", type="text", nullable=true)
     */
    private $paypal_email;

    /**
     * @var integer
     *
     * @ORM\Column(name="balance", type="integer")
     */
    private $balance = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->Blogs = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add blog.
     *
     * @param Eccube\Entity\Product $blog
     *
     * @return Customer
     */
    public function addBlog(Blog $blog)
    {
        $this->Blogs[] = $blog;

        return $this;
    }

    /**
     * Remove blog.
     *
     * @param Eccube\Entity\Product $blog
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeBlog(Blog $blog)
    {
        return $this->Blogs->removeElement($blog);
    }

    /**
     * Get blogs.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBlogs()
    {
        return $this->Blogs;
    }    

    /**
     * Set image.
     *
     * @param string $image
     *
     * @return this
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image.
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set nick_name.
     *
     * @param string $nick_name
     *
     * @return this
     */
    public function setNickName($nick_name)
    {
        $this->nick_name = $nick_name;

        return $this;
    }

    /**
     * Get nick_name.
     *
     * @return string
     */
    public function getNickName()
    {
        return $this->nick_name;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set paypal_email.
     *
     * @param string $paypal_email
     *
     * @return this
     */
    public function setPaypalEmail($paypal_email)
    {
        $this->paypal_email = $paypal_email;

        return $this;
    }

    /**
     * Get paypal_email.
     *
     * @return string
     */
    public function getPaypalEmail()
    {
        return $this->paypal_email;
    }
    
    /**
     * Set balance.
     *
     * @param int $balance
     *
     * @return this
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * Get balance.
     *
     * @return int
     */
    public function getBalance()
    {
        return $this->balance;
    }
}