<?php

namespace Plugin\PayPalCheckout\Form\Type\Admin\PaymentStatus;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class TransactionConditionType
 * @package Plugin\PayPalCheckout\Form\Type\Admin\PaymentStatus
 */
class TransactionConditionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('Payments', ChoiceType::class, [
                'choices' => [],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('OrderStatuses', ChoiceType::class, [
                'choices' => [],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('PaymentStatuses', ChoiceType::class, [
                'choices' => [],
                'multiple' => true,
                'expanded' => true,
            ]);
    }
}
