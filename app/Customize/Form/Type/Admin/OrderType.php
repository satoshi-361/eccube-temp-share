<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\Form\Type\Admin;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Entity\Payment;
use Eccube\Form\DataTransformer;
use Eccube\Form\Type\AddressType;
use Eccube\Form\Type\KanaType;
use Eccube\Form\Type\NameType;
use Eccube\Form\Type\PhoneNumberType;
use Eccube\Form\Type\PostalType;
use Eccube\Form\Type\PriceType;
use Eccube\Form\Validator\Email;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\OrderStateMachine;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use Eccube\Form\Type\Admin\OrderType as BaseType;
use Eccube\Form\Type\Admin\OrderItemType;

class OrderType extends BaseType
{
    /**
     * OrderType constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param EccubeConfig $eccubeConfig
     * @param OrderStateMachine $orderStateMachine
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        EccubeConfig $eccubeConfig,
        OrderStateMachine $orderStateMachine,
        OrderStatusRepository $orderStatusRepository
    ) {
        parent::__construct( $entityManager, $eccubeConfig, $orderStateMachine, $orderStatusRepository );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', NameType::class, [
                'required' => false,
                'options' => [
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                ],
            ])
            ->add('email', EmailType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Email(['strict' => $this->eccubeConfig['eccube_rfc_email_check']]),
                ],
            ])
            ->add('phone_number', PhoneNumberType::class, [
                'required' => false,
            ])
            ->add('message', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_ltext_len'],
                    ]),
                ],
            ])
            ->add('discount', PriceType::class, [
                'required' => false,
            ])
            ->add('delivery_fee_total', PriceType::class, [
                'required' => false,
            ])
            ->add('charge', PriceType::class, [
                'required' => false,
            ])
            ->add('use_point', NumberType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => "/^\d+$/u",
                        'message' => 'form_error.numeric_only',
                    ]),
                ],
            ])
            ->add('note', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_ltext_len'],
                    ]),
                ],
            ])
            ->add('Payment', EntityType::class, [
                'required' => false,
                'class' => Payment::class,
                'choice_label' => function (Payment $Payment) {
                    return $Payment->isVisible()
                        ? $Payment->getMethod()
                        : $Payment->getMethod().trans('admin.common.hidden_label');
                },
                'placeholder' => false,
                'query_builder' => function ($er) {
                    return $er->createQueryBuilder('p')
                        ->orderBy('p.visible', 'DESC')  // 非表示は下に配置
                        ->addOrderBy('p.sort_no', 'ASC');
                },
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('OrderItems', CollectionType::class, [
                'entry_type' => OrderItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
            ])
            ->add('OrderItemsErrors', TextType::class, [
                'mapped' => false,
            ])
            ->add('return_link', HiddenType::class, [
                'mapped' => false,
            ]);

        $builder
            ->add($builder->create('Customer', HiddenType::class)
                ->addModelTransformer(new DataTransformer\EntityToIdTransformer(
                    $this->entityManager,
                    '\Eccube\Entity\Customer'
                )));

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'sortOrderItems']);
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'addOrderStatusForm']);
        // $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'addShippingForm']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'copyFields']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'validateOrderStatus']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'validateOrderItems']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'associateOrderAndShipping']);
    }
}
