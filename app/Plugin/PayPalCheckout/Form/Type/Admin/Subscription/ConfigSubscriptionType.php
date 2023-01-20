<?php

namespace Plugin\PayPalCheckout\Form\Type\Admin\Subscription;

use Plugin\PayPalCheckout\Entity\ConfigSubscription;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ConfigSubscriptionType
 * @package Plugin\PayPalCheckout\Form\Type\Admin
 */
class ConfigSubscriptionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('next_payment_date', DateType::class, [
            'label' => trans('paypal.admin.subscription_configure.form.next_payment_date'),
            'widget' => 'single_text',
            'html5' => false,
            'format' => "yyyy/MM/dd",
            'attr' => ['class' => 'datepicker'],
            'required' => false,
            'constraints' => [
                new Assert\NotBlank([
                    'message' => trans('paypal.admin.subscription_configure.assert.not_blank')
                ]),
                new Assert\Date([
                    'message' => trans('paypal.admin.subscription_configure.assert.date')
                ])
            ]
        ]);

        $builder->add('is_deleted', CheckboxType::class, [
            'label' => trans('paypal.admin.subscription_configure.form.is_deleted'),
            'value' => '1',
            'required' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ConfigSubscription::class
        ]);
    }
}
