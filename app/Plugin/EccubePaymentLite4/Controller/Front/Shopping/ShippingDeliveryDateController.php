<?php

namespace Plugin\EccubePaymentLite4\Controller\Front\Shopping;

use Eccube\Controller\AbstractController;
use Exception;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\RegularCycle;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Repository\RegularCycleRepository;
use Plugin\EccubePaymentLite4\Service\CalculateNextDeliveryDateService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ShippingDeliveryDateController extends AbstractController
{
    /**
     * @var RegularCycleRepository
     */
    private $regularCycleRepository;
    /**
     * @var CalculateNextDeliveryDateService
     */
    private $calculateNextDeliveryDateService;
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(
        RegularCycleRepository $regularCycleRepository,
        ConfigRepository $configRepository,
        CalculateNextDeliveryDateService $calculateNextDeliveryDateService
    ) {
        $this->regularCycleRepository = $regularCycleRepository;
        $this->configRepository = $configRepository;
        $this->calculateNextDeliveryDateService = $calculateNextDeliveryDateService;
    }

    /**
     * @Route(
     *     "/shopping/eccube_payment_lite/regular/shipping_delivery_date",
     *     name="eccube_payment_lite4_shipping_delivery_date",
     *     methods={"POST"}
     * )
     *
     * @throws Exception
     */
    public function index(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }
        $this->isTokenValid();

        if (empty($request->request->get('shipping_delivery_date'))) {
            /** @var Config $Config */
            $Config = $this->configRepository->find(1);
            $addDay = $Config->getFirstDeliveryDays();
            $firstDeliveryDate = new \DateTime('+'.$Config->getFirstDeliveryDays().' day');
        } else {
            $firstDeliveryDate = new \DateTime($request->request->get('shipping_delivery_date'));
            $addDay = $this->getDiffShippingDeliveryDate($firstDeliveryDate);
        }

        /** @var RegularCycle $RegularCycle */
        $RegularCycle = $this
            ->regularCycleRepository
            ->find($request->request->get('regular_cycle'));
        $nextDeliveryDate = $this
            ->calculateNextDeliveryDateService
            ->calc($RegularCycle, $addDay);

        return $this->json([
            'first_delivery_date' => $this->getDayWithWeekDay(clone $firstDeliveryDate),
            'next_delivery_date' => $this->getDayWithWeekDay(clone $nextDeliveryDate),
        ]);
    }

    private function getDayWithWeekDay($datetime): string
    {
        $week = [
            '日',
            '月',
            '火',
            '水',
            '木',
            '金',
            '土',
        ];

        return $datetime->format('Y/m/d').'('.$week[(int) $datetime->format('w')].')';
    }

    private function getDiffShippingDeliveryDate(\DateTime $shippingDeliveryDate): int
    {
        $today = new \DateTime('today');
        $interval = $today->diff($shippingDeliveryDate);

        return (int) $interval->format('%a');
    }
}
