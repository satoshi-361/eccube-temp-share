<?php

namespace Plugin\EccubePaymentLite4\Form\Type\Admin;

use Eccube\Form\Type\MasterType;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegularStatusType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => RegularStatus::class,
            'choice_label' => 'name',
            'placeholder' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return MasterType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'regular_status';
    }
}
