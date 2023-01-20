<?php

namespace Plugin\PayPalCheckout\Service\Method;

use Eccube\Entity\Order;
use Eccube\Service\Payment\PaymentDispatcher;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Symfony\Component\Form\FormInterface;

/**
 * Class Subscription
 * @package Plugin\PayPalCheckout\Service\Method
 */
class Subscription implements PaymentMethodInterface
{

    /**
     * 決済の妥当性を検証し, 検証結果を返します.
     *
     * 主にクレジットカードの有効性チェック等を実装します.
     *
     * @return PaymentResult
     */
    public function verify()
    {
        // do nothing
    }

    /**
     * 決済を実行し, 実行結果を返します.
     *
     * 主に決済の確定処理を実装します.
     *
     * @return PaymentResult
     */
    public function checkout()
    {
        // do nothing
    }

    /**
     * 注文に決済を適用します.
     *
     * PaymentDispatcher に遷移先の情報を設定することで, 他のコントローラに処理を移譲できます.
     *
     * @return PaymentDispatcher
     */
    public function apply()
    {
        // do nothing
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
        // do nothing
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
        // do nothing
    }
}
