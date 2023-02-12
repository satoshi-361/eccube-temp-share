<?php

namespace Plugin\EccubePaymentLite4\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;

class RegularDiscountEditType extends AbstractType
{
    /**
     * Build Form
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('regular_discount_items', CollectionType::class, [
            'entry_type' => RegularDiscountItemEditType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'error_bubbling' => false,
        ]);
    }
}
