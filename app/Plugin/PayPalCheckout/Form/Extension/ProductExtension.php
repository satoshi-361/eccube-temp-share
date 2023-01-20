<?php

namespace Plugin\PayPalCheckout\Form\Extension;

use Eccube\Form\Type\Admin\ProductClassType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Class ProductExtension
 * @package Plugin\PayPalCheckout\Form\Extension
 */
class ProductExtension extends AbstractTypeExtension
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $form->remove('subscription_pricing')
                ->remove('use_subscription');
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $form->remove('subscription_pricing')
                ->remove('use_subscription');
        });
    }

    /**
     * {@inheritdoc}
     *
     * EC-CUBE4.0.x(Symfony3.4)との互換性用
     */
    public function getExtendedType()
    {
        return ProductClassType::class;
    }

    /**
     * Return the class of the type being extended.
     */
    public static function getExtendedTypes(): iterable
    {
        return [ProductClassType::class];
    }
}
