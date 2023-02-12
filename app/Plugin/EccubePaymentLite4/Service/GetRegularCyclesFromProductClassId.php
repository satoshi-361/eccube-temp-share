<?php

namespace Plugin\EccubePaymentLite4\Service;

use Eccube\Repository\ProductClassRepository;
use Plugin\EccubePaymentLite4\Entity\RegularCycle;

class GetRegularCyclesFromProductClassId
{
    /**
     * @var ProductClassRepository
     */
    private $productClassRepository;

    public function __construct(
        ProductClassRepository $productClassRepository
    ) {
        $this->productClassRepository = $productClassRepository;
    }

    public function handle(int $productClassId)
    {
        $RegularCycles = $this->productClassRepository->find($productClassId)->getRegularCycle();
        usort($RegularCycles, function (RegularCycle $r1, RegularCycle $r2) {
            return $r1->getSortNo() < $r2->getSortNo() ? 1 : -1;
        });

        return $RegularCycles;
    }
}
