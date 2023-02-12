<?php

namespace Plugin\EccubePaymentLite4\Form\Type\Front;

use Eccube\Common\EccubeConfig;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Plugin\EccubePaymentLite4\Form\Type\Front\RegularOrderItemType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class RegularProductQuantityType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    public function __construct(
        EccubeConfig $eccubeConfig
    ) {
        $this->eccubeConfig = $eccubeConfig;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('RegularOrderItems', CollectionType::class, [
                'entry_type' => RegularOrderItemType::class,
                'allow_add' => false,
                'allow_delete' => false,
                'prototype' => false,
            ]);
    }
}
