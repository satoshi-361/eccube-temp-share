<?php
namespace Plugin\SitemapXmlGenerator\ValueObject;

use Plugin\SitemapXmlGenerator\Entity\SitemapSetting;

/**
 * SitemapUrl
 *
 * @author Masaki Okada
 */
class SitemapUrl
{

    /**
     * 更新頻度リスト
     *
     * @var array
     */
    const CHANGEFREQ_LIST = [
        0 => 'none',
        1 => 'always',
        2 => 'hourly',
        3 => 'daily',
        4 => 'weekly',
        5 => 'monthly',
        6 => 'yearly',
        7 => 'never'
    ];

    /**
     * ページURL
     *
     * @var string
     */
    private $loc;

    /**
     * 最終更新日
     *
     * @var \DateTime
     */
    private $lastmod;

    /**
     * 更新頻度
     *
     * @var string
     */
    private $changefreq;

    /**
     * 優先順位
     *
     * @var string
     */
    private $priority;

    /**
     * ページURL
     *
     * @return string
     */
    public function getLoc()
    {
        return $this->loc;
    }

    /**
     * 最終更新日
     *
     * @return \DateTime
     */
    public function getLastmod()
    {
        return $this->lastmod;
    }

    /**
     * 更新頻度
     *
     * @return string
     */
    public function getChangefreq()
    {
        return $this->changefreq;
    }

    /**
     * 優先順位
     *
     * @return string
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * ページURL
     *
     * @param string $loc
     */
    public function setLoc($loc)
    {
        $this->loc = $loc;
    }

    /**
     * 最終更新日
     *
     * @param \DateTime $lastmod
     */
    public function setLastmod($lastmod)
    {
        $this->lastmod = $lastmod;
    }

    /**
     * 更新頻度
     *
     * @param string $changefreq
     */
    public function setChangefreq($changefreq)
    {
        $this->changefreq = $changefreq;
    }

    /**
     * 優先順位
     *
     * @param string $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * 全ての項目をセットする
     *
     * @param SitemapSetting $SitemapSetting
     * @param string $loc
     * @param string $lastmod
     * @param string $pageUrl
     */
    public function setSitemapUrl(SitemapSetting $SitemapSetting, $loc, $lastmod, $pageUrl)
    {
        // URLをセット
        $this->loc = $loc;

        // 最終更新日をセット
        if ($SitemapSetting->getLastmodAvailed()) {
            $this->setLastmod($lastmod);
        }

        // 更新頻度
        $changefreq = $SitemapSetting->getChangefreq();
        if ($changefreq != 0) {
            $this->setChangefreq(self::CHANGEFREQ_LIST[$changefreq]);
        }

        // 優先度
        if ($SitemapSetting->getPriorityAvailed()) {
            if (strcmp('homepage', $pageUrl) === 0) {
                $this->priority = '1.0';
            } else if (strcmp('product_detail', $pageUrl) === 0) {
                $this->priority = '0.7';
            } else if (strcmp('product_list', $pageUrl) === 0) {
                $this->priority = '0.5';
            } else {
                $this->priority = '0.3';
            }
        }
    }
}