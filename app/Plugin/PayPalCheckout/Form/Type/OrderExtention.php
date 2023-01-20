<?php

namespace Plugin\PayPalCheckout\Form\Type;

use Eccube\Form\Type\Shopping\OrderType;
use Eccube\Repository\PaymentRepository;
use Symfony\Component\Form\AbstractTypeExtension;

/**
 * Class OrderExtention
 */
class OrderExtention extends AbstractTypeExtension
{
    protected $paymentRepository;

    public function __construct(
        PaymentRepository $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * {@inheritdoc}
     *
     * EC-CUBE4.0.x(Symfony3.4)との互換性用
     */
    public function getExtendedType()
    {
        return OrderType::class;
    }

    /**
     * Return the class of the type being extended.
     */
    public static function getExtendedTypes(): iterable
    {
        return [OrderType::class];
    }
}
