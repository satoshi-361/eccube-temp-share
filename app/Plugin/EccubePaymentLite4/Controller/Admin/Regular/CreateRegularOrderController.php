<?php

namespace Plugin\EccubePaymentLite4\Controller\Admin\Regular;

use DateTime;
use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Service\Payment\Method\Cash;
use Exception;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularOrderItem;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Repository\RegularOrderRepository;
use Plugin\EccubePaymentLite4\Repository\RegularStatusRepository;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestCreateRegularOrderService;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestGetUserInfoService;
use Plugin\EccubePaymentLite4\Service\IsActiveRegularService;
use Plugin\EccubePaymentLite4\Service\IsExpireCreditCardService;
use Plugin\EccubePaymentLite4\Service\IsResumeRegularOrder;
use Plugin\EccubePaymentLite4\Service\Mail\OrderCreationBatchResultMailService;
use Plugin\EccubePaymentLite4\Service\Mail\RegularSpecifiedCountNotificationMailService;
use Plugin\EccubePaymentLite4\Service\Method\Credit;
use Plugin\EccubePaymentLite4\Service\Method\Reg_Credit;
use Plugin\EccubePaymentLite4\Service\NextDeliveryDateService;
use Plugin\EccubePaymentLite4\Service\RegularCreditService;
use Plugin\EccubePaymentLite4\Service\UpdateNormalPaymentOrderService;
use Plugin\EccubePaymentLite4\Service\UpdateRegularOrderService;
use Plugin\EccubePaymentLite4\Service\UpdateRegularStatusService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CreateRegularOrderController extends AbstractController
{
    /**
     * @var RegularCreditService
     */
    private $regularCreditService;
    /**
     * @var RegularOrderRepository
     */
    private $regularOrderRepository;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var RequestCreateRegularOrderService
     */
    private $requestCreateRegularOrderService;
    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;
    /**
     * @var NextDeliveryDateService
     */
    private $nextDeliveryDateService;
    /**
     * @var UpdateNormalPaymentOrderService
     */
    private $updateNormalPaymentOrderService;
    /**
     * @var UpdateRegularOrderService
     */
    private $updateRegularOrderService;
    /**
     * @var IsActiveRegularService
     */
    private $isActiveRegularService;
    /**
     * @var OrderCreationBatchResultMailService
     */
    private $orderCreationBatchResultMailService;
    /**
     * @var RegularStatusRepository
     */
    private $regularStatusRepository;
    /**
     * @var IsResumeRegularOrder
     */
    private $isResumeRegularOrder;
    /**
     * @var RequestGetUserInfoService
     */
    private $requestGetUserInfoService;
    /**
     * @var IsExpireCreditCardService
     */
    private $isExpireCreditCardService;
    /**
     * @var UpdateRegularStatusService
     */
    private $updateRegularStatusService;
    /**
     * @var RegularSpecifiedCountNotificationMailService
     */
    private $regularSpecifiedCountNotificationMailService;

    public function __construct(
        RegularCreditService $regularCreditService,
        RequestCreateRegularOrderService $requestCreateRegularOrderService,
        RegularOrderRepository $regularOrderRepository,
        ConfigRepository $configRepository,
        NextDeliveryDateService $nextDeliveryDateService,
        UpdateNormalPaymentOrderService $updateNormalPaymentOrderService,
        UpdateRegularOrderService $updateRegularOrderService,
        CsrfTokenManagerInterface $csrfTokenManager,
        IsActiveRegularService $isActiveRegularService,
        OrderCreationBatchResultMailService $orderCreationBatchResultMailService,
        RegularStatusRepository $regularStatusRepository,
        SessionInterface $session,
        IsResumeRegularOrder $isResumeRegularOrder,
        RequestGetUserInfoService $requestGetUserInfoService,
        IsExpireCreditCardService $isExpireCreditCardService,
        UpdateRegularStatusService $updateRegularStatusService,
        RegularSpecifiedCountNotificationMailService $regularSpecifiedCountNotificationMailService
    ) {
        $this->regularCreditService = $regularCreditService;
        $this->requestCreateRegularOrderService = $requestCreateRegularOrderService;
        $this->regularOrderRepository = $regularOrderRepository;
        $this->configRepository = $configRepository;
        $this->nextDeliveryDateService = $nextDeliveryDateService;
        $this->updateNormalPaymentOrderService = $updateNormalPaymentOrderService;
        $this->updateRegularOrderService = $updateRegularOrderService;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->isActiveRegularService = $isActiveRegularService;
        $this->orderCreationBatchResultMailService = $orderCreationBatchResultMailService;
        $this->regularStatusRepository = $regularStatusRepository;
        $this->session = $session;
        $this->isResumeRegularOrder = $isResumeRegularOrder;
        $this->requestGetUserInfoService = $requestGetUserInfoService;
        $this->isExpireCreditCardService = $isExpireCreditCardService;
        $this->updateRegularStatusService = $updateRegularStatusService;
        $this->regularSpecifiedCountNotificationMailService = $regularSpecifiedCountNotificationMailService;
    }

    /**
     * @Route(
     *     "/%eccube_admin_route%/eccube_payment_lite/regular/add",
     *     name="eccube_payment_lite4_admin_regular_index_add",
     *     methods={"POST"}
     * )
     */
    public function index(Request $request)
    {
        if (!$this->isActiveRegularService->isActive()) {
            throw new NotFoundHttpException();
        }
        $this->isTokenValid();
        try {
            $this->entityManager->beginTransaction();

            $page_no = $this->session->get('eccube.admin.regular.order.search.page_no');
            $page_no = $page_no ?? 1;
            $SystemErrorRegularOrderIds = $this->session->get('eccube.admin.regular.order.systemErrorRegularOrderIds') ? $this->session->get('eccube.admin.regular.order.systemErrorRegularOrderIds') : [];
            $PaymentErrorRegularOrderIds = $this->session->get('eccube.admin.regular.order.paymentErrorRegularOrderIds') ? $this->session->get('eccube.admin.regular.order.paymentErrorRegularOrderIds') : [];
            $SuccessRegularOrderIds = $this->session->get('eccube.admin.regular.order.successRegularOrderIds') ? $this->session->get('eccube.admin.regular.order.successRegularOrderIds') : [];
            $RegularOrderIds = $this->session->get('eccube.admin.regular.order.regularOrdersIds') ? $this->session->get('eccube.admin.regular.order.regularOrdersIds') : [];

            $ids = $request->get('ids');
            if (empty($ids)) {
                // 受注作成バッチ結果メール送信
                $PaymentErrorRegularOrders = [];
                if (!empty($PaymentErrorRegularOrderIds)) {
                    $PaymentErrorRegularOrders = $this->regularOrderRepository->findBy([
                        'id' => $PaymentErrorRegularOrderIds,
                    ]);
                }
                $this
                    ->orderCreationBatchResultMailService
                    ->sendMail(
                        $PaymentErrorRegularOrders,
                        count($RegularOrderIds),
                        count($SuccessRegularOrderIds),
                        count($PaymentErrorRegularOrderIds),
                        count($SystemErrorRegularOrderIds)
                    );
                $this->entityManager->commit();
                $this->addSuccess('admin.common.save_complete', 'admin');
                $this->clearSession();

                return $this->redirectToRoute(
                    'eccube_payment_lite4_admin_regular_index_page', [
                    'page_no' => $page_no,
                ]);
            }
            /** @var Config $Config */
            $Config = $this->configRepository->find(1);
            $regularOrderId = (int) array_shift($ids);
            $RegularOrderIds[] = $regularOrderId;
            $this->session->set('eccube.admin.regular.order.regularOrdersIds', $RegularOrderIds);
            /** @var RegularOrder $RegularOrder */
            $RegularOrder = $this->regularOrderRepository->find($regularOrderId);

            // 休止再開期間が過ぎている受注を解約(再開期限切れ)とする。
            if (!$this->isResumeRegularOrder->handle($RegularOrder)) {
                $this->changeRegularStatus($RegularOrder, RegularStatus::CANCELLATION_EXPIRED_RESUMPTION);
            }
            // （次回お届け予定日）>= （実行日） + （締め日）となる受注を取得
            $deadLineStartDate = new DateTime('today');
            $deadLineStartDate->modify('+'.$Config->getRegularOrderDeadline().' day');
            $deadLineEndDate = new DateTime('tomorrow');
            $deadLineEndDate->modify('+'.$Config->getRegularOrderDeadline().' day');

            /** @var RegularOrder $TargetRegularOrder */
            $TargetRegularOrder = $this
                ->regularOrderRepository
                ->getRegularOrderCanBeCreated(
                    $regularOrderId,
                    $deadLineStartDate,
                    $deadLineEndDate
                );
            if (is_null($TargetRegularOrder)) {
                $this->addWarning(
                    '定期ID: '.$regularOrderId.'について、定期受注作成対象の受注が存在しないため、処理をスキップしました。',
                    'admin'
                );
                $this->entityManager->commit();

                return $this->forwardRoute($ids);
            }
            // 対象の定期受注番号を取得
            $Order = $this
                ->regularCreditService
                ->createOrder($RegularOrder);
            // 定期受注マスタを最新化
            $this->updateRegularOrderService->update($RegularOrder, $Order);
            // リダイレクト先でセッションが必要になるため、受注未作成のregular_order_idをセッションに保存
            $this->session->set('eccube.admin.regular.order.createRegularIds', $ids);

            // 商品が存在するかチェック
            if (!$this->isExistProductClass($RegularOrder)) {
                $SystemErrorRegularOrderIds[] = $RegularOrder->getId();
                $this->session->set('eccube.admin.regular.order.systemErrorRegularOrderIds', $SystemErrorRegularOrderIds);
                $this->changeRegularStatus($RegularOrder, RegularStatus::SYSTEM_ERROR);
                $this->entityManager->commit();

                return $this->forwardRoute($ids);
            }
            // 指定定期回数の通知
            $regularSpecifiedCountNotification = $Config->getRegularSpecifiedCountNotificationMail();
            // 定期回数［regular_order_count］＋１の場合、定期指定回数お知らせメールを注文者へ送信する
            if ($regularSpecifiedCountNotification == $TargetRegularOrder->getRegularOrderCount() + 1) {
                $this
                    ->regularSpecifiedCountNotificationMailService
                    ->sendMail($TargetRegularOrder);
            }

            if (!is_null($Order->getPayment()) &&
                ($Order->getPayment()->getMethodClass() === Credit::class ||
                $Order->getPayment()->getMethodClass() === Reg_Credit::class)) {
                $userInfoResults = $this->requestGetUserInfoService->handle($Order->getCustomer()->getId());
                if ($userInfoResults['status'] === 'NG') {
                    $PaymentErrorRegularOrderIds[] = $RegularOrder->getId();
                    $this->session->set('eccube.admin.regular.order.paymentErrorRegularOrderIds', $PaymentErrorRegularOrderIds);
                    $this->changeRegularStatus($RegularOrder, RegularStatus::PAYMENT_ERROR);
                    $this->addError(
                        '定期ID: '.$RegularOrder->getId().' エラーコード: '.$userInfoResults['err_code'].' エラーメッセージ: '.$userInfoResults['message'],
                        'admin'
                    );
                    $this->entityManager->commit();

                    return $this->forwardRoute($ids);
                }
                if ($userInfoResults['status'] === 'OK') {
                    if ($this->isExpireCreditCardService->handle($userInfoResults['cardExpire'])) {
                        $PaymentErrorRegularOrderIds[] = $RegularOrder->getId();
                        $this->session->set('eccube.admin.regular.order.paymentErrorRegularOrderIds', $PaymentErrorRegularOrderIds);
                        $this->changeRegularStatus($RegularOrder, RegularStatus::PAYMENT_ERROR);
                        $this->addError(
                            '定期ID: '.$RegularOrder->getId().'について、クレジットカードの有効期限が切れているため、受注の作成・イプシロン決済サービス決済の登録は行っていません。',
                            'admin'
                        );
                        logs('gmo_epsilon')->addWarning('定期ID: '.$RegularOrder->getId().'について、クレジットカードの有効期限が切れているため、受注の作成・イプシロン決済サービス決済の登録は行っていません。');
                        $this->entityManager->commit();

                        return $this->forwardRoute($ids);
                    }
                }
                $results = $this->requestCreateRegularOrderService->handle($RegularOrder, $Order, 'eccube_payment_lite4_admin_regular_index_add');
                if ($results['status'] === 'NG') {
                    $PaymentErrorRegularOrderIds[] = $RegularOrder->getId();
                    $this->session->set('eccube.admin.regular.order.paymentErrorRegularOrderIds', $PaymentErrorRegularOrderIds);
                    $this->changeRegularStatus($RegularOrder, RegularStatus::PAYMENT_ERROR);
                    $this->addError(
                        '定期ID: '.$RegularOrder->getId().' エラーコード: '.$results['err_code'].' エラーメッセージ: '.$results['message'],
                        'admin'
                    );
                    $this->entityManager->commit();

                    return $this->forwardRoute($ids);
                }
                if ($results['status'] === 'OK') {
                    $SuccessRegularOrderIds[] = $RegularOrder->getId();
                    $this->session->set('eccube.admin.regular.order.successRegularOrderIds', $SuccessRegularOrderIds);
                    $this->entityManager->commit();

                    return $this->redirect($results['redirectUrl']);
                }
            } elseif (!is_null($Order->getPayment()) && $Order->getPayment()->getMethodClass() === Cash::class) {
                $this->updateNormalPaymentOrderService->updateAfterMakingOrder($Order);
                $this->updateRegularOrderService->updateAfterMakingPayment($RegularOrder);
                $SuccessRegularOrderIds[] = $RegularOrder->getId();
                $this->session->set('eccube.admin.regular.order.successRegularOrderIds', $SuccessRegularOrderIds);
                $this->entityManager->commit();
            } else {
                $this->addDanger(
                    '定期ID: '.$TargetRegularOrder->getId().'について、定期受注で選択できる支払方法ではないため、受注の作成・イプシロン決済サービス決済の登録は行っていません。',
                    'admin'
                );
            }
            $this->entityManager->commit();

            return $this->forwardRoute($ids);
        } catch (Exception $e) {
            $this->entityManager->rollback();
            $this->clearSession();
            $this->addError('予期せぬエラーが発生したため、処理をロールバックしました。'.$e->getMessage(), 'admin');
            logs('gmo_epsilon')->addError($e->getMessage());

            return $this->redirectToRoute(
                'eccube_payment_lite4_admin_regular_index_page', [
                'page_no' => 1,
            ]);
        }
    }

    /**
     * @param $ids
     */
    private function forwardRoute($ids): Response
    {
        return $this->forwardToRoute(
            'eccube_payment_lite4_admin_regular_index_add',
            [],
            [
                'ids' => $ids,
                Constant::TOKEN_NAME => $this->csrfTokenManager->getToken(Constant::TOKEN_NAME)->getValue(),
            ]
        );
    }

    private function isExistProductClass(RegularOrder $RegularOrder): bool
    {
        /** @var RegularOrderItem[] $RegularProductOrderItems */
        $RegularProductOrderItems = $RegularOrder->getRegularProductOrderItems();
        $count = $this
            ->regularOrderRepository
            ->getNotAbolishedProductClass(
                $RegularProductOrderItems[0]->getProductClass()->getProduct()->getId()
            );
        if ($count === 0) {
            return false;
        }

        return true;
    }

    private function changeRegularStatus(RegularOrder $RegularOrder, int $regularOrderStatusId)
    {
        $RegularStatus = $this->updateRegularStatusService->handle($RegularOrder, $regularOrderStatusId);
        $this->addInfo(
            '定期ID: '.$RegularOrder->getId().'について、定期ステータスを'.$RegularStatus->getName().'に変更しました。',
            'admin'
        );
    }

    private function clearSession()
    {
        if ($this->session->has('eccube.admin.regular.order.systemErrorRegularOrderIds')) {
            $this->session->remove('eccube.admin.regular.order.systemErrorRegularOrderIds');
        }
        if ($this->session->has('eccube.admin.regular.order.paymentErrorRegularOrderIds')) {
            $this->session->remove('eccube.admin.regular.order.paymentErrorRegularOrderIds');
        }
        if ($this->session->has('eccube.admin.regular.order.successRegularOrderIds')) {
            $this->session->remove('eccube.admin.regular.order.successRegularOrderIds');
        }
        if ($this->session->has('eccube.admin.regular.order.regularOrdersIds')) {
            $this->session->remove('eccube.admin.regular.order.regularOrdersIds');
        }
    }
}
