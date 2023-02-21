<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\Twig\Extension;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Repository\ProductRepository;
use Eccube\Repository\OrderItemRepository;
use Twig\TwigFunction;

use Eccube\Twig\Extension\EccubeExtension as BaseExtension;

class EccubeExtension extends BaseExtension
{
    /**
     * @var OrderItemRepository
     */
    private $orderItemRepository;

    /**
     * EccubeExtension constructor.
     *
     * @param EccubeConfig $eccubeConfig
     * @param ProductRepository $productRepository
     * @param OrderItemRepository $orderItemRepository
     */
    public function __construct(EccubeConfig $eccubeConfig, ProductRepository $productRepository, OrderItemRepository $orderItemRepository)
    {
        parent::__construct( $eccubeConfig, $productRepository );
        
        $this->orderItemRepository = $orderItemRepository;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[] An array of functions
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('has_errors', [$this, 'hasErrors']),
            new TwigFunction('active_menus', [$this, 'getActiveMenus']),
            new TwigFunction('class_categories_as_json', [$this, 'getClassCategoriesAsJson']),
            new TwigFunction('product', [$this, 'getProduct']),
            new TwigFunction('php_*', [$this, 'getPhpFunctions'], ['pre_escape' => 'html', 'is_safe' => ['html']]),
            new TwigFunction('currency_symbol', [$this, 'getCurrencySymbol']),
            new TwigFunction('is_blog_purchased', [$this, 'isBlogPurchased']),
        ];
    }

    /**
     * 記事の購入可否を確認
     *
     * @param \Eccube\Entity\Customer $Customer
     * @param $id
     *
     * @return boolean
     */
    public function isBlogPurchased($Customer, $id)
    {
        $qb = $this->orderItemRepository->createQueryBuilder('oi');
        $qb
            ->innerJoin('oi.Product', 'p')
            ->innerJoin('oi.Order', 'o')
            ->where('p.id = :id')
            ->andWhere('o.Customer = :Customer')
            ->andWhere($qb->expr()->notIn('o.OrderStatus', ':status'))
            ->setParameter('id', $id)
            ->setParameter('Customer', $Customer)
            ->setParameter('status', [OrderStatus::PROCESSING, OrderStatus::PENDING]);

        if ( count($qb->getQuery()->getResult()) ) {
            return true;
        }

        return false;
    }
}
