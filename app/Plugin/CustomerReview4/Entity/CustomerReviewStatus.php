<?php

namespace Plugin\CustomerReview4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Master\AbstractMasterEntity;

/**
 * CustomerReviewStatus
 *
 * @ORM\Table(name="plg_customer_review_status")
 * @ORM\Entity(repositoryClass="Plugin\CustomerReview4\Repository\CustomerReviewStatusRepository")
 */
class CustomerReviewStatus extends AbstractMasterEntity
{
    /**
     * 新規投稿
     */
    const POST = 1;

    /**
     * 承認(表示)
     */
    const SHOW = 2;

    /**
     * 非承認(非表示)
     */
    const HIDE = 3;
}
