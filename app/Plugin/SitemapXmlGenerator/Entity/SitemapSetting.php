<?php
/*
 * Copyright(c) 2020 YAMATO.CO.LTD
 */
namespace Plugin\SitemapXmlGenerator\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SitemapSetting
 *
 * @ORM\Table(name="plg_sitemap_setting")
 * @ORM\Entity
 */
class SitemapSetting
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var bool
     *
     * @ORM\Column(name="lastmod_availed", type="boolean", nullable=false, options={"comment"="最終更新日取得可能"})
     */
    private $lastmodAvailed;

    /**
     * @var int
     *
     * @ORM\Column(name="changefreq", type="smallint", nullable=false, options={"comment"="サイトの更新頻度"})
     */
    private $changefreq;

    /**
     * @var bool
     *
     * @ORM\Column(name="priority_availed", type="boolean", nullable=false, options={"comment"="優先度利用可否"})
     */
    private $priorityAvailed;

    /**
     * @var string|null
     *
     * @ORM\Column(name="excluded_directories", type="string", length=1024, nullable=true, options={"comment"="除外ディレクトリ"})
     */
    private $excludedDirectories;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetime", nullable=false, options={"comment"="作成日時"})
     */
    private $createDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetime", nullable=false, options={"comment"="更新日時"})
     */
    private $updateDate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="discriminator_type", type="string", length=255, nullable=true, options={"comment"="識別タイプ"})
     */
    private $discriminatorType;



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
     * Set lastmodAvailed.
     *
     * @param bool $lastmodAvailed
     *
     * @return SitemapSetting
     */
    public function setLastmodAvailed($lastmodAvailed)
    {
        $this->lastmodAvailed = $lastmodAvailed;

        return $this;
    }

    /**
     * Get lastmodAvailed.
     *
     * @return bool
     */
    public function getLastmodAvailed()
    {
        return $this->lastmodAvailed;
    }

    /**
     * Set changefreq.
     *
     * @param int $changefreq
     *
     * @return SitemapSetting
     */
    public function setChangefreq($changefreq)
    {
        $this->changefreq = $changefreq;

        return $this;
    }

    /**
     * Get changefreq.
     *
     * @return int
     */
    public function getChangefreq()
    {
        return $this->changefreq;
    }

    /**
     * Set priorityAvailed.
     *
     * @param bool $priorityAvailed
     *
     * @return SitemapSetting
     */
    public function setPriorityAvailed($priorityAvailed)
    {
        $this->priorityAvailed = $priorityAvailed;

        return $this;
    }

    /**
     * Get priorityAvailed.
     *
     * @return bool
     */
    public function getPriorityAvailed()
    {
        return $this->priorityAvailed;
    }

    /**
     * Set excludedDirectories.
     *
     * @param string|null $excludedDirectories
     *
     * @return SitemapSetting
     */
    public function setExcludedDirectories($excludedDirectories = null)
    {
        $this->excludedDirectories = $excludedDirectories;

        return $this;
    }

    /**
     * Get excludedDirectories.
     *
     * @return string|null
     */
    public function getExcludedDirectories()
    {
        return $this->excludedDirectories;
    }

    /**
     * Set createDate.
     *
     * @param \DateTime $createDate
     *
     * @return SitemapSetting
     */
    public function setCreateDate($createDate)
    {
        $this->createDate = $createDate;

        return $this;
    }

    /**
     * Get createDate.
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }

    /**
     * Set updateDate.
     *
     * @param \DateTime $updateDate
     *
     * @return SitemapSetting
     */
    public function setUpdateDate($updateDate)
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    /**
     * Get updateDate.
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
    }

    /**
     * Set discriminatorType.
     *
     * @param string|null $discriminatorType
     *
     * @return SitemapSetting
     */
    public function setDiscriminatorType($discriminatorType = null)
    {
        $this->discriminatorType = $discriminatorType;

        return $this;
    }

    /**
     * Get discriminatorType.
     *
     * @return string|null
     */
    public function getDiscriminatorType()
    {
        return $this->discriminatorType;
    }
}
