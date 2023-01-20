<?php

/*
 * This file is part of OrderBySale4
 *
 * Copyright(c) U-Mebius Inc. All Rights Reserved.
 *
 * https://umebius.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\OrderBySale4\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Config
 *
 * @ORM\Table(name="plg_order_by_sale4_config")
 * @ORM\Entity(repositoryClass="Plugin\OrderBySale4\Repository\ConfigRepository")
 */
class Config
{
    const ORDER_BY_AMOUNT = 1;
    const ORDER_BY_QUANTITY = 2;

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
     * @ORM\Column(type="smallint", options={"unsigned":true})
     */
    private $product_list_order_by_id;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", options={"unsigned":true})
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(type="smallint", nullable=false, options={"default" : 6})
     */
    private $block_display_number = 6;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $target_days;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this;
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return int
     */
    public function getProductListOrderById()
    {
        return $this->product_list_order_by_id;
    }

    /**
     * @param int $product_list_order_by_id
     *
     * @return Config
     */
    public function setProductListOrderById($product_list_order_by_id)
    {
        $this->product_list_order_by_id = $product_list_order_by_id;

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     *
     * @return Config
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getBlockDisplayNumber()
    {
        return $this->block_display_number;
    }

    /**
     * @param int $block_display_number
     *
     * @return Config
     */
    public function setBlockDisplayNumber($block_display_number)
    {
        $this->block_display_number = $block_display_number;

        return $this;
    }

    /**
     * @return int
     */
    public function getTargetDays()
    {
        return $this->target_days;
    }

    /**
     * @param int $target_days
     */
    public function setTargetDays($target_days): Config
    {
        $this->target_days = $target_days;

        return $this;
    }
}
