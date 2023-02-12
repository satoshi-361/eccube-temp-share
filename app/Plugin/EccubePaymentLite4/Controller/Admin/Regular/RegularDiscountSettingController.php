<?php

namespace Plugin\EccubePaymentLite4\Controller\Admin\Regular;

use Eccube\Controller\AbstractController;
use Eccube\Entity\ProductClass;
use Eccube\Repository\ProductClassRepository;
use Plugin\EccubePaymentLite4\Entity\RegularDiscount;
use Plugin\EccubePaymentLite4\Form\Type\Admin\RegularDiscountMatrixType;
use Plugin\EccubePaymentLite4\Repository\RegularDiscountRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class RegularDiscountSettingController extends AbstractController
{
    /**
     * @var RegularDiscountRepository
     */
    private $regularDiscountRepository;

    /**
     * @var ProductClassRepository
     */
    private $productClassRepository;

    /**
     * RegularDiscountSettingController constructor.
     */
    public function __construct(
        RegularDiscountRepository $regularDiscountRepository,
        ProductClassRepository $productClassRepository
    ) {
        $this->regularDiscountRepository = $regularDiscountRepository;
        $this->productClassRepository = $productClassRepository;
    }

    /**
     * 定期継続回数に応じた割引を設定する機能を追加
     *
     * @Route(
     *     "/%eccube_admin_route%/eccube_payment_lite/regular/discount_setting",
     *     name="eccube_payment_lite4_admin_regular_discount_setting"
     * )
     * @Template("@EccubePaymentLite4/admin/Regular/Setting/discount.twig")
     */
    public function index(Request $request)
    {
        $discountIdMax = $this->getDiscountIdMax();
        $data = $this->getRegularDiscountData();
        if (empty($data)) {
            $data = $this->initialData();
        }

        $form = $this->createForm(RegularDiscountMatrixType::class, [
            'regular_discounts' => $data,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = $form['regular_discounts']->getData();
                if (!empty($data)) {
                    $ids = [];
                    foreach ($data as $group) {
                        $items = !empty($group['regular_discount_items']) ? $group['regular_discount_items'] : [];
                        /** @var RegularDiscount $RegularDiscount */
                        foreach ($items as $key => $RegularDiscount) {
                            $discountId = $RegularDiscount->getId();
                            // 更新の場合にIDを記録
                            if (!empty($discountId)) {
                                $ids[] = $discountId;
                            }
                            // 初回の場合はregular_countに1を設定する
                            if (is_null($RegularDiscount->getRegularCount()) && $key == 0) {
                                $RegularDiscount->setRegularCount(1);
                            }

                            $this->entityManager->persist($RegularDiscount);
                        }
                    }

                    // filter & remove
                    $RegularDiscounts = $this->regularDiscountRepository->findAll();
                    foreach ($RegularDiscounts as $RegularDiscount) {
                        $discountId = $RegularDiscount->getId();
                        // 更新の場合かつ、フォームより送信されたdiscount_idが含まれない場合は削除する
                        if (!empty($discountId) && !in_array($discountId, $ids)) {
                            $this->entityManager->remove($RegularDiscount);
                        }
                    }

                    $this->entityManager->flush();
                    $this->addSuccess('admin.common.save_complete', 'admin');

                    return $this->redirectToRoute('eccube_payment_lite4_admin_regular_discount_setting');
                }
            } catch (\Exception $e) {
                log_info('定期回数割引削除エラー', [$e]);
                $this->addError('関連するデータがあるため定期回数割引を削除できませんでした', 'admin');

                return $this->redirectToRoute('eccube_payment_lite4_admin_regular_discount_setting');
            }
        }

        return [
            'form' => $form->createView(),
            'discountIdMax' => $discountIdMax,
        ];
    }

    private function getRegularDiscountData(): array
    {
        $data = [];
        $groups = [];

        /** @var RegularDiscount[] $RegularDiscounts */
        $RegularDiscounts = $this->regularDiscountRepository->findAll();
        foreach ($RegularDiscounts as $RegularDiscount) {
            $discountId = $RegularDiscount->getDiscountId();
            $groups[$discountId] = !empty($groups[$discountId]) ? array_merge($groups[$discountId], [$RegularDiscount]) : [$RegularDiscount];
        }

        foreach ($groups as $group) {
            $data[] = ['regular_discount_items' => $group];
        }

        return $data;
    }

    private function initialData()
    {
        $groupNumber = 3;
        $itemNumber = 3;
        $data = [];

        for ($i = 1; $i <= $groupNumber; $i++) {
            $group = [];
            for ($j = 1; $j <= $itemNumber; $j++) {
                $RegularDiscount = new RegularDiscount();
                $RegularDiscount
                    ->setDiscountId($i)
                    ->setItemId($j);

                $group[] = $RegularDiscount;
            }

            $data[] = ['regular_discount_items' => $group];
        }

        return $data;
    }

    private function getDiscountIdMax()
    {
        /** @var RegularDiscount $RegularDiscount */
        $RegularDiscount = $this->regularDiscountRepository->findOneBy([], [
            'discount_id' => 'DESC',
        ]);
        /** @var ProductClass $ProductClass */
        $ProductClass = $this->productClassRepository->findOneBy([], [
            'RegularDiscount' => 'DESC',
        ]);

        $discountIdMax = $RegularDiscount ? $RegularDiscount->getDiscountId() : 0;
        if (!empty($ProductClass->getRegularDiscount()) && $ProductClass->getRegularDiscount()->getDiscountId() > $discountIdMax) {
            $discountIdMax = $ProductClass->getRegularDiscount()->getDiscountId();
        }

        return $discountIdMax;
    }
}
