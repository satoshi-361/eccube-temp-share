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

namespace Plugin\Pickup4\Service;

use Plugin\Pickup4\Entity\RecommendProduct;
use Plugin\Pickup4\Repository\RecommendProductRepository;

/**
 * Class RecommendService.
 */
class RecommendService
{
    /**
     * @var RecommendProductRepository
     */
    private $recommendProductRepository;

    /**
     * RecommendService constructor.
     *
     * @param RecommendProductRepository $recommendProductRepository
     */
    public function __construct(RecommendProductRepository $recommendProductRepository)
    {
        $this->recommendProductRepository = $recommendProductRepository;
    }

    /**
     * ピックアップ商品情報を新規登録する
     *
     * @param $data
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function createRecommend($data)
    {
        // ピックアップ商品詳細情報を生成する
        $Recommend = $this->newRecommend($data);

        return $this->recommendProductRepository->saveRecommend($Recommend);
    }

    /**
     * ピックアップ商品情報を更新する
     *
     * @param $data
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function updateRecommend($data)
    {
        // ピックアップ商品情報を取得する
        $Recommend = $this->recommendProductRepository->find($data['id']);
        if (!$Recommend) {
            return false;
        }

        // ピックアップ商品情報を書き換える
        $Recommend->setComment($data['comment']);
        $Recommend->setProduct($data['Product']);

        // ピックアップ商品情報を更新する
        return $this->recommendProductRepository->saveRecommend($Recommend);
    }

    /**
     * ピックアップ商品情報を生成する
     *
     * @param $data
     *
     * @return RecommendProduct
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function newRecommend($data)
    {
        $rank = $this->recommendProductRepository->getMaxRank();

        $Recommend = new RecommendProduct();
        $Recommend->setComment($data['comment']);
        $Recommend->setProduct($data['Product']);
        $Recommend->setSortno(($rank ? $rank : 0) + 1);
        $Recommend->setVisible(true);

        return $Recommend;
    }
}
