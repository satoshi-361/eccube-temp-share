<?php

namespace Plugin\PayPalCheckout\Service\Method;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\PayPalCheckout\Entity\Config;
use Plugin\PayPalCheckout\Exception\PayPalCheckoutException;
use Plugin\PayPalCheckout\Repository\ConfigRepository;
use Plugin\PayPalCheckout\Service\PayPalAcdcService;
use Plugin\PayPalCheckout\Service\PayPalService;
use Symfony\Component\Form\FormInterface;

/**
 * Class Acdc
 * @package Plugin\PayPalCheckout\Service\Method
 */
class Acdc implements PaymentMethodInterface
{
    /**
     * @var Config
     */
    private $Config;
    /**
     * @var PayPalService
     */
    private $paypal;
    /**
     * @var PayPalAcdcService
     */
    private $payPalAcdcService;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;
    /**
     * @var Order
     */
    private $Order;
    /**
     * @var FormInterface
     */
    private $form;
    /**
     * @var PurchaseFlow
     */
    private $purchaseFlow;

    /**
     * CreditCard constructor.
     * @param PayPalService $paypal
     * @param PayPalAcdcService $payPalAcdcService
     * @param OrderStatusRepository $orderStatusRepository
     * @param EntityManagerInterface $entityManager
     * @param PurchaseFlow $shoppingPurchaseFlow
     */
    public function __construct(
        ConfigRepository $configRepository,
        PayPalService $paypal,
        PayPalAcdcService $payPalAcdcService,
        OrderStatusRepository $orderStatusRepository,
        EntityManagerInterface $entityManager,
        PurchaseFlow $shoppingPurchaseFlow
    ) {
        $this->Config = $configRepository->get();
        $this->paypal = $paypal;
        $this->payPalAcdcService = $payPalAcdcService;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->entityManager = $entityManager;
        $this->purchaseFlow = $shoppingPurchaseFlow;
    }

    /**
     * 決済の妥当性を検証し, 検証結果を返します.
     *
     * @return PaymentResult
     */
    public function verify()
    {
        /** @var PaymentResult $paymentResult */
        $paymentResult = new PaymentResult();
        $vaultId = $this->payPalAcdcService->getVaultIdFromSession();
        // 「3Dセキュアが有効」かつ「vault決済でない」場合、3Dセキュア認証済みか確認する
        if ($this->Config->getUse3dsecure() && empty($vaultId)) {
            if ($this->paypal->verify3dsecure()) {
                $success = true;
            } else {
                $success = false;
                $paymentResult->setErrors(['3Dセキュア認証に失敗しました。再度入力し直してください。']);
            }
        } else {
            $success = true;
        }
        $paymentResult->setSuccess($success);
        return $paymentResult;
    }

    /**
     * 決済を実行し, 実行結果を返します.
     *
     * 主に決済の確定処理を実装します.
     *
     * @return PaymentResult
     * @throws \Eccube\Service\PurchaseFlow\PurchaseException
     */
    public function checkout()
    {
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PAID);
        $this->Order->setOrderStatus($OrderStatus);
        $this->Order->setPaymentDate(new DateTime());
        $this->purchaseFlow->commit($this->Order, new PurchaseContext());

        // forced order editing
        $this->Order->setOrderStatus($OrderStatus);
        $this->Order->setPaymentDate(new DateTime());

        // vault にクレジットカード情報を保存する場合、既に保存されている vault があれば決済完了後に消しにいく
        $saveVault = $this->payPalAcdcService->extractSaveVaultFromSession();
        if ($saveVault) {
            $vault = $this->payPalAcdcService->getVaults();
        }

        /** @var PaymentResult $paymentResult */
        $paymentResult = new PaymentResult();
        $success = false;
        try {
            $this->paypal->checkout($this->Order);
            $paymentResult->setSuccess(true);
            $success = true;
        } catch (PayPalCheckoutException $e) {
            $paymentResult->setSuccess(false);
            $paymentResult->setErrors([
                'message' => $e->getMessage(),
            ]);
        }

        // vault を消しにいく
        if ($success && $saveVault && !empty($vault)) {
            $this->payPalAcdcService->deleteVaultFromId($vault[0]['id']);
        }
        return $paymentResult;
    }

    /**
     * 注文に決済を適用します.
     *
     * PaymentDispatcher に遷移先の情報を設定することで, 他のコントローラに処理を移譲できます.
     *
     * @return void
     * @throws \Eccube\Service\PurchaseFlow\PurchaseException
     */
    public function apply()
    {
        /** @var OrderStatus $orderStatus */
        $orderStatus = $this->orderStatusRepository->find(OrderStatus::PENDING);
        $this->Order->setOrderStatus($orderStatus);
        $this->purchaseFlow->prepare($this->Order, new PurchaseContext());
    }

    /**
     * PaymentMethod の処理に必要な FormInterface を設定します.
     *
     * @param FormInterface
     *
     * @return PaymentMethod
     */
    public function setFormType(FormInterface $form)
    {
        $this->form = $form;
    }

    /**
     * この決済を使用する Order を設定します.
     *
     * @param Order
     *
     * @return PaymentMethod
     */
    public function setOrder(Order $Order)
    {
        $this->Order = $Order;
    }
}
