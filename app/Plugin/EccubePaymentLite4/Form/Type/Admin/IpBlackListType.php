<?php

namespace Plugin\EccubePaymentLite4\Form\Type\Admin;

use Plugin\EccubePaymentLite4\Entity\IpBlackList;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class IpBlackListType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('ip_address', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'common.select',
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('sort_no', HiddenType::class, [
                'label' => false,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => IpBlackList::class,
        ]);
    }
}
