<?php

namespace Plugin\CMBlogPro\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductStatus
 *
 * @ORM\Table(name="plg_blog_mtb_status2")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\CMBlogPro\Repository\BlogStatusRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class BlogStatus extends \Eccube\Entity\Master\AbstractMasterEntity
{
    /**
     * 公開
     *
     * @var integer
     */
    const DISPLAY_SHOW = 1;

    /**
     * 非公開
     *
     * @var integer
     */
    const DISPLAY_HIDE = 2;

    /**
     * 廃止
     *
     * @var integer
     */
    const DISPLAY_ABOLISHED = 3;
}
