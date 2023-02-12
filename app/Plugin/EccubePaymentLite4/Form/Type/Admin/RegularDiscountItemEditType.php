<?php

namespace Plugin\EccubePaymentLite4\Form\Type\Admin;

use Plugin\EccubePaymentLite4\Entity\RegularDiscount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegularDiscountItemEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('discount_id', HiddenType::class)
            ->add('item_id', HiddenType::class)
            ->add('regular_count', ChoiceType::class, [
                'choices' => array_combine(range(1, 10), range(1, 10)),
                'required' => false,
                'placeholder' => '--',
            ])
            ->add('discount_rate', ChoiceType::class, [
                'choices' => array_combine(range(0, 100), range(0, 100)),
                'required' => false,
                'placeholder' => '--',
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RegularDiscount::class,
        ]);
    }
}
