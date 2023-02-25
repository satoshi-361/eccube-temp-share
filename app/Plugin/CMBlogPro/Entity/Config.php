<?php

namespace Plugin\CMBlogPro\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Config
 *
 * @ORM\Table(name="plg_blog_config2")
 * @ORM\Entity(repositoryClass="Plugin\CMBlogPro\Repository\ConfigRepository")
 */
class Config
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
     * @var int
     *
     * @ORM\Column(name="display_block", type="smallint", options={"unsigned":true, "default":4})
     */
    private $display_block;

    /**
     * @var int
     *
     * @ORM\Column(name="display_page", type="smallint", options={"unsigned":true, "default":8})
     */
    private $display_page;

    /**
     * @var string
     *
     * @ORM\Column(name="title_en", type="string", length=255, nullable=false, options={"default":"Blog"})
     */
    private $title_en;

    /**
     * @var string
     *
     * @ORM\Column(name="title_jp", type="string", length=255, nullable=false, options={"default":"ブログ"})
     */
    private $title_jp;

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
     * Get Display Block
     * 
     * @return int
     */
    public function getDisplayBlock()
    {
        return $this->display_block;
    }

    /**
     * Set Display Block
     * 
     * @param int $value
     *
     * @return $this;
     */
    public function setDisplayBlock($value)
    {
        $this->display_block = $value;
        return $this;
    }

    /**
     * Get Display Page
     * 
     * @return int
     */
    public function getDisplayPage()
    {
        return $this->display_page;
    }

    /**
     * Set Display Page
     * 
     * @param int $value
     *
     * @return $this;
     */
    public function setDisplayPage($value)
    {
        $this->display_page = $value;
        return $this;
    }

    /**
     * Get title (english)
     * 
     * @return string
     */
    public function getTitleEn()
    {
        return $this->title_en;
    }

    /**
     * Set title (english)
     * 
     * @param string $value
     *
     * @return $this;
     */
    public function setTitleEn($value)
    {
        $this->title_en = $value;
        return $this;
    }

    /**
     * Get title (japanese)
     * 
     * @return string
     */
    public function getTitleJp()
    {
        return $this->title_jp;
    }

    /**
     * Set title (japanese)
     * 
     * @param string $value
     *
     * @return $this;
     */
    public function setTitleJp($value)
    {
        $this->title_jp = $value;
        return $this;
    }

}
