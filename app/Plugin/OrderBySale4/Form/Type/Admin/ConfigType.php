<?php

/*
 * This file is part of OrderBySale4
 *
 * Copyright(c) U-Mebius Inc. All Rights Reserved.
 *
 * https://umebius.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\OrderBySale4\Form\Type\Admin;

use Plugin\OrderBySale4\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ConfigType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 255]),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                  '売上金額順' => Config::ORDER_BY_AMOUNT,
                  '売上個数順' => Config::ORDER_BY_QUANTITY,
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('block_display_number', ChoiceType::class, [
                'required' => true,
                'label' => 'ブロック内商品表示数',
                'constraints' => [
                    new NotBlank(),
                ],
                'choices' => array_combine(range(1, 30), range(1, 30)),
            ])
            ->add('target_days', IntegerType::class, [
                'required' => false,
                'label' => '集計日数',
                'constraints' => [
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
            'data_class' => Config::class,
        ]);
    }
}
