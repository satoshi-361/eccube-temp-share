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
use Plugin\PayPalCheckout\Exception\PayPalCheckoutException;
use Plugin\PayPalCheckout\Service\PayPalService;
use Symfony\Component\Form\FormInterface;

/**
 * Class CreditCard
 * @package Plugin\PayPalCheckout\Service\Method
 */
class CreditCard implements PaymentMethodInterface
{
    /* @var PayPalService */
    private $paypal;
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
     * @param OrderStatusRepository $orderStatusRepository
     * @param EntityManagerInterface $entityManager
     * @param PurchaseFlow $shoppingPurchaseFlow
     */
    public function __construct(
        PayPalService $paypal,
        OrderStatusRepository $orderStatusRepository,
        EntityManagerInterface $entityManager,
        PurchaseFlow $shoppingPurchaseFlow
    ) {
        $this->paypal = $paypal;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->entityManager = $entityManager;
        $this->purchaseFlow = $shoppingPurchaseFlow;
    }

    /**
     * 決済の妥当性を検証し, 検証結果を返します.
     *
     * 主にクレジットカードの有効性チェック等を実装します.
     *
     * @return PaymentResult
     */
    public function verify()
    {
//        //継続商品を購入する有効性チェック
//        if($this->contInfo->getPayType($this->Order) == 'error')
//        {
//            $result = new PaymentResult();
//            $result->setSuccess(false);
//            $result->setErrors(['定期商品は個別で購入してください']);
//            return $result;
//        }
        /** @var PaymentResult $paymentResult */
        $paymentResult = new PaymentResult();
        $paymentResult->setSuccess(true);
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

        /** @var PaymentResult $paymentResult */
        $paymentResult = new PaymentResult();
        try {
            $this->paypal->checkout($this->Order);
            $paymentResult->setSuccess(true);
        } catch (PayPalCheckoutException $e) {
            $paymentResult->setSuccess(false);
            $paymentResult->setErrors([
                'message' => $e->getMessage()
            ]);
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
