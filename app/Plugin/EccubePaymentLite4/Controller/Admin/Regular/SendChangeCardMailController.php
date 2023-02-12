<?php

namespace Plugin\EccubePaymentLite4\Controller\Admin\Regular;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Customer;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularShipping;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Repository\RegularShippingRepository;
use Plugin\EccubePaymentLite4\Service\ChangeCardNotificationMailService;
use Plugin\EccubePaymentLite4\Service\IsActiveRegularService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class SendChangeCardMailController extends AbstractController
{
    /**
     * @var RegularShippingRepository
     */
    private $regularShippingRepository;
    /**
     * @var ChangeCardNotificationMailService
     */
    private $changeCardNotificationMailService;
    /**
     * @var IsActiveRegularService
     */
    private $isActiveRegularService;
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(
        ChangeCardNotificationMailService $changeCardNotificationMailService,
        RegularShippingRepository $regularShippingRepository,
        ConfigRepository $configRepository,
        IsActiveRegularService $isActiveRegularService
    ) {
        $this->changeCardNotificationMailService = $changeCardNotificationMailService;
        $this->regularShippingRepository = $regularShippingRepository;
        $this->configRepository = $configRepository;
        $this->isActiveRegularService = $isActiveRegularService;
    }

    /**
     * @Route(
     *     "/%eccube_admin_route%/regular/eccube_payment_lite/regular/{id}/send_change_mail",
     *     requirements={"id" = "\d+"},
     *     name="eccube_payment_lite4_admin_send_change_mail",
     *     methods={"PUT"}
     * )
     */
    public function notifyMail(RegularShipping $RegularShipping): JsonResponse
    {
        if (!$this->isActiveRegularService->isActive()) {
            throw new NotFoundHttpException();
        }
        $this->isTokenValid();
        $today = new \DateTime();
        /** @var RegularOrder $RegularOrder */
        $RegularOrder = $RegularShipping->getRegularOrder();
        /** @var Customer $Customer */
        $Customer = $RegularOrder
            ->getCustomer();
        // クレジット有効期限切れの会員かチェック
        $expireDateTime = $RegularShipping
            ->getRegularOrder()
            ->getCustomer()
            ->getGmoEpsilonCreditCardExpirationDate();
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        $expireDateTime->modify('- '.$Config->getCardExpirationNotificationDays().'day');
        // 継続の定期ステータスかどうかチェック
        if ($RegularOrder->getRegularStatus()->getId() !== RegularStatus::CONTINUE) {
            return $this->jsonResponse(
                false,
                '定期受注ID: '.$RegularShipping->getRegularOrder()->getId().'は定期ステータスが「継続」ではないため処理をスキップしました。',
                'NG'
            );
        }
        // クレジットカード有効期限通知範囲内で有効期限切れ通知メール送信済みの場合は処理を行わない
        $cardChangeRequestMailSendDate = $Customer->getCardChangeRequestMailSendDate();
        if (!empty($cardChangeRequestMailSendDate) && $expireDateTime < $cardChangeRequestMailSendDate) {
            return $this->jsonResponse(
                false,
                '定期受注ID: '.$RegularShipping->getRegularOrder()->getId().'は、クレジットカード有効期限通知範囲内で有効期限切れ通知メール送信済みのため処理をスキップしました。',
                'NG'
            );
        }
        // クレジットカード有効期限通知日が過ぎている場合はメールを送信
        if ($expireDateTime < $today) {
            /** @var Customer $Customer */
            $Customer = $RegularShipping->getRegularOrder()->getCustomer();
            $this->changeCardNotificationMailService->sendMail($Customer);
            $Customer->setCardChangeRequestMailSendDate(new \DateTime());
            $this->entityManager->persist($Customer);
            $this->entityManager->flush();

            return $this->jsonResponse(
                true,
                '定期受注ID: '.$RegularShipping->getRegularOrder()->getId().'の会員にカード変更依頼メールを送信しました。',
                'OK'
            );
        }

        return $this->jsonResponse(
            false,
            '定期受注ID: '.$RegularShipping->getRegularOrder()->getId().'は、クレジットカードの有効期限が有効期限切れ通知日数の範囲外のため、処理をスキップしました。',
            'NG'
        );
    }

    private function jsonResponse($isSendMail, $message, $status): JsonResponse
    {
        return $this->json([
            'mail' => $isSendMail,
            'message' => $message,
            'status' => $status,
        ]);
    }
}
