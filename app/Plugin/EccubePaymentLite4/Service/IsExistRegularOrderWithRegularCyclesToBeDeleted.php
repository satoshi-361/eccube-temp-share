<?php

namespace Plugin\EccubePaymentLite4\Service;

use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Plugin\EccubePaymentLite4\Repository\RegularOrderRepository;

class IsExistRegularOrderWithRegularCyclesToBeDeleted
{
    /**
     * @var RegularOrderRepository
     */
    private $regularOrderRepository;

    public function __construct(
        RegularOrderRepository $regularOrderRepository
    ) {
        $this->regularOrderRepository = $regularOrderRepository;
    }

    public function isExist($originalRegularCycles, $targetRegularCycles): bool
    {
        // 定期受注で「解約」以外の定期ステータスの定期受注が存在する場合、
        // 商品登録から定期サイクルを解除出来ないようにフォームの入力チェックを行う
        $originalRegularCycleIds = [];
        foreach ($originalRegularCycles as $RegularCycle) {
            $originalRegularCycleIds[] = $RegularCycle->getId();
        }
        $targetRegularCycleIds = [];
        foreach ($targetRegularCycles as $RegularCycle) {
            $targetRegularCycleIds[] = $RegularCycle->getId();
        }
        $deleteTargetRegularCycleIds = array_diff($originalRegularCycleIds, $targetRegularCycleIds);
        /** @var RegularOrder[] $RegularOrders */
        $RegularOrders = $this->regularOrderRepository->findBy([
            'RegularStatus' => [
                RegularStatus::CONTINUE,
                RegularStatus::SUSPEND,
                RegularStatus::PAYMENT_ERROR,
                RegularStatus::SYSTEM_ERROR,
                RegularStatus::WAITING_RE_PAYMENT,
            ],
            'RegularCycle' => $deleteTargetRegularCycleIds,
        ]);
        if (empty($RegularOrders)) {
            return false;
        }

        return true;
    }
}
