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

namespace Plugin\EccubePaymentLite4\Service\PurchaseFlow\Processor;

use Eccube\Annotation\OrderFlow;
use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\ItemInterface;
use Eccube\Entity\Order;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Service\PurchaseFlow\Processor\AddPointProcessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Service\IsRegularPaymentService;

/**
 * @ShoppingFlow
 * @OrderFlow
 */
class RegularAddPointProcessor extends AddPointProcessor
{
    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var ConfigRepository
     */
    private $epsilonConfig;

    /**
     * @var IsRegularPaymentService
     */
    private $isRegularPaymentService;

    public function __construct(
        BaseInfoRepository $baseInfoRepository,
        ConfigRepository $configRepository,
        IsRegularPaymentService $isRegularPaymentService
    ) {
        parent::__construct($baseInfoRepository);
        $this->BaseInfo = $baseInfoRepository->get();
        $this->epsilonConfig = $configRepository->get();
        $this->isRegularPaymentService = $isRegularPaymentService;
    }

    public function validate(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        if (!$this->supports($itemHolder)) {
            return;
        }

        // 付与ポイントを計算
        $addPoint = $this->calculateAddPoint($itemHolder);
        $itemHolder->setAddPoint($addPoint);
    }

    /**
     * 定期商品の付与ポイントを計算.
     */
    private function calculateAddPoint(ItemHolderInterface $itemHolder)
    {
        $basicPointRate = $this->BaseInfo->getBasicPointRate();

        $regularPointMagnification = $this->epsilonConfig->getRegularPointMagnification();

        $Shipping = $itemHolder->getShippings()->first();
        if ($Shipping) {
            if ($Shipping->getDelivery()->getSaleType()->getName() !== '定期商品') {
                $regularPointMagnification = 1;
            }
        }
        if (!$this->isRegularPaymentService->isRegularPayment($itemHolder)) {
            $regularPointMagnification = 1;
        }

        // 明細ごとのポイントを集計
        $totalPoint = array_reduce($itemHolder->getItems()->toArray(),
            function ($carry, ItemInterface $item) use ($basicPointRate, $regularPointMagnification) {
                $pointRate = $item->isProduct() ? $item->getProductClass()->getPointRate() : null;
                if ($pointRate === null) {
                    $pointRate = $basicPointRate;
                }

                // 通常購入時と比較したポイント付与倍率（「basic_point_rate」に設定した値を掛けた倍率。）
                //　0：0*1=0。定期商品はポイント付与しない。
                //　1：1*1=1。定期商品は通常商品と同じ 100円の1%＝ 1 Pt
                //　2：2*1=2。定期商品は通常商品の2倍 100円の2%= 2 Pt
                $pointRate = $regularPointMagnification * $pointRate;
                $point = 0;
                if ($item->isPoint()) {
                    $point = round($item->getPrice() * ($pointRate / 100)) * $item->getQuantity();
                // Only calc point on product
                } elseif ($item->isProduct()) {
                    // ポイント = 単価 * ポイント付与率 * 数量
                    $point = round($item->getPrice() * ($pointRate / 100)) * $item->getQuantity();
                } elseif ($item->isDiscount()) {
                    $point = round($item->getPrice() * ($pointRate / 100)) * $item->getQuantity();
                }

                return $carry + $point;
            }, 0);

        return $totalPoint < 0 ? 0 : $totalPoint;
    }

    /**
     * Processorが実行出来るかどうかを返す.
     *
     * 以下を満たす場合に実行できる.
     *
     * - ポイント設定が有効であること.
     * - $itemHolderがOrderエンティティであること.
     * - 会員のOrderであること.
     */
    private function supports(ItemHolderInterface $itemHolder): bool
    {
        if (!$this->BaseInfo->isOptionPoint()) {
            return false;
        }

        if (!$itemHolder instanceof Order) {
            return false;
        }

        if (!$itemHolder->getCustomer()) {
            return false;
        }

        return true;
    }
}
