<?php

namespace Plugin\EccubePaymentLite4\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;

class RegularDiscountMatrixType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('regular_discounts', CollectionType::class, [
            'entry_type' => RegularDiscountEditType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'error_bubbling' => false,
        ]);
    }
}
