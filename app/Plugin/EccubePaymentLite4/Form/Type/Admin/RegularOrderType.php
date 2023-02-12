<?php

namespace Plugin\EccubePaymentLite4\Form\Type\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Customer;
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
use Plugin\EccubePaymentLite4\Entity\RegularCycle;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularOrderItem;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Plugin\EccubePaymentLite4\Repository\RegularStatusRepository;
use Plugin\EccubePaymentLite4\Service\GetRegularCyclesFromProductClassId;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RegularOrderType extends AbstractType
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var OrderStateMachine
     */
    protected $orderStateMachine;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var RegularStatusRepository
     */
    protected $regularStatusRepository;
    /**
     * @var GetRegularCyclesFromProductClassId
     */
    private $getRegularCyclesFromProductClassId;

    public function __construct(
        EntityManagerInterface $entityManager,
        EccubeConfig $eccubeConfig,
        OrderStateMachine $orderStateMachine,
        OrderStatusRepository $orderStatusRepository,
        RegularStatusRepository $regularStatusRepository,
        GetRegularCyclesFromProductClassId $getRegularCyclesFromProductClassId
    ) {
        $this->entityManager = $entityManager;
        $this->eccubeConfig = $eccubeConfig;
        $this->orderStateMachine = $orderStateMachine;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->regularStatusRepository = $regularStatusRepository;
        $this->getRegularCyclesFromProductClassId = $getRegularCyclesFromProductClassId;
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
            ->add('kana', KanaType::class, [
                'required' => false,
                'options' => [
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                ],
            ])
            ->add('company_name', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_stext_len'],
                    ]),
                ],
            ])
            ->add('postal_code', PostalType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'options' => [
                    'attr' => ['class' => 'p-postal-code'],
                ],
            ])
            ->add('address', AddressType::class, [
                'required' => false,
                'pref_options' => [
                    'constraints' => [
                        new Assert\NotBlank(),
                    ],
                    'attr' => ['class' => 'p-region-id'],
                ],
                'addr01_options' => [
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length([
                            'max' => $this->eccubeConfig['eccube_mtext_len'],
                        ]),
                    ],
                    'attr' => ['class' => 'p-locality p-street-address'],
                ],
                'addr02_options' => [
                    'required' => false,
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length([
                            'max' => $this->eccubeConfig['eccube_mtext_len'],
                        ]),
                    ],
                    'attr' => ['class' => 'p-extended-address'],
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
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('company_name', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_stext_len'],
                    ]),
                ],
            ])
            ->add('message', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_ltext_len'],
                    ]),
                ],
            ])
            ->add('delivery_fee_total', PriceType::class, [
                'required' => false,
            ])
            ->add('charge', PriceType::class, [
                'required' => false,
            ])
            ->add('note', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_ltext_len'],
                    ]),
                ],
            ])
            ->add('RegularOrderItems', CollectionType::class, [
                'entry_type' => RegularOrderItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
            ])
            ->add('RegularOrderItemsErrors', TextType::class, [
                'mapped' => false,
            ])
            ->add('return_link', HiddenType::class, [
                'mapped' => false,
            ]);

        $builder
            ->add($builder->create('Customer', HiddenType::class)
                ->addModelTransformer(new DataTransformer\EntityToIdTransformer(
                    $this->entityManager,
                    Customer::class
                )));

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'sortOrderItems']);
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'addOrderStatusForm']);
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'addShippingForm']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'copyFields']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'validateOrderItems']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'associateOrderAndShipping']);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'addRegularCycleForm']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RegularOrder::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'regular_order';
    }

    public function sortOrderItems(FormEvent $event)
    {
        /** @var RegularOrder $RegularOrder */
        $RegularOrder = $event->getData();
        if (null === $RegularOrder) {
            return;
        }
        $RegularOrderItems = $RegularOrder->getItems();

        $form = $event->getForm();
        $form['RegularOrderItems']->setData($RegularOrderItems);
    }

    public function addRegularCycleForm(FormEvent $event)
    {
        $form = $event->getForm();
        /** @var RegularOrder $RegularOrder */
        $RegularOrder = $event->getData();
        /** @var RegularOrderItem $RegularOrderItem */
        $productClassId = $RegularOrder->getRegularProductOrderItems()[0]->getProductClass()->getId();
        $form
            ->add('RegularCycle', ChoiceType::class, [
                'choices' => $this->getRegularCyclesFromProductClassId->handle($productClassId),
                'choice_value' => 'id',
                'choice_label' => function (RegularCycle $regularCycle) {
                    return $regularCycle;
                },
                'expanded' => true,
                'multiple' => false,
                'mapped' => true,
            ])
        ;
    }

    public function addOrderStatusForm(FormEvent $event)
    {
        /** @var RegularOrder $RegularOrder */
        $RegularOrder = $event->getData();
        $regularStatusId = $RegularOrder->getRegularStatus()->getId();
        // 解約の場合は、フォームを表示しない。
        if ($regularStatusId === RegularStatus::CANCELLATION) {
            return;
        }
        $RegularStatuses = $this->regularStatusRepository->findBy([
            'id' => [
                RegularStatus::CONTINUE,
                RegularStatus::CANCELLATION,
                RegularStatus::SUSPEND,
                RegularStatus::PAYMENT_ERROR,
                RegularStatus::SYSTEM_ERROR,
                RegularStatus::WAITING_RE_PAYMENT,
                RegularStatus::CANCELLATION_EXPIRED_RESUMPTION,
            ],
        ]);

        $form = $event->getForm();
        $form
            ->add('RegularStatus', EntityType::class, [
            'class' => RegularStatus::class,
            'choices' => $RegularStatuses,
            'choice_label' => 'name',
            'constraints' => [
                new Assert\NotBlank(),
            ],
            'expanded' => true,
            'multiple' => false,
            'data' => $RegularOrder->getRegularStatus(),
        ]);
    }

    /**
     * 単一配送時に, Shippingのフォームを追加する.
     * 複数配送時はShippingの編集は行わない.
     */
    public function addShippingForm(FormEvent $event)
    {
        /** @var RegularOrder $RegularOrder */
        $RegularOrder = $event->getData();

        // 複数配送時はShippingの編集は行わない
        if ($RegularOrder && $RegularOrder->isMultiple()) {
            return;
        }

        $data = $RegularOrder ? $RegularOrder->getRegularShippings()->first() : null;
        $form = $event->getForm();
        $form->add('RegularShipping', RegularShippingType::class, [
            'mapped' => false,
            'data' => $data,
        ]);
    }

    /**
     * フォームからPOSTされない情報をコピーする.
     *
     * - 支払方法の名称
     * - 会員の性別/職業/誕生日
     * - 受注ステータス(新規登録時)
     */
    public function copyFields(FormEvent $event)
    {
        /** @var RegularOrder $RegularOrder */
        $RegularOrder = $event->getData();

        // 支払方法の名称をコピーする.
        if ($Payment = $RegularOrder->getPayment()) {
            $RegularOrder->setPaymentMethod($Payment->getMethod());
        }

        // 会員受注の場合、会員の性別/職業/誕生日をエンティティにコピーする
        if ($Customer = $RegularOrder->getCustomer()) {
            $RegularOrder->setSex($Customer->getSex());
            $RegularOrder->setJob($Customer->getJob());
            $RegularOrder->setBirth($Customer->getBirth());
        }
    }

    /**
     * 受注明細のバリデーションを行う.
     * 商品明細が1件も登録されていない場合はエラーとする.
     */
    public function validateOrderItems(FormEvent $event)
    {
        /** @var RegularOrder $RegularOrder */
        $RegularOrder = $event->getData();
        $Items = $RegularOrder->getRegularOrderItems();

        $count = 0;
        foreach ($Items as $Item) {
            if ($Item->isProduct()) {
                $count++;
            }
        }
        // 商品明細が1件もない場合はエラーとする.
        if ($count < 1) {
            // 画面下部にエラーメッセージを表示させる
            $form = $event->getForm();
            $form['RegularOrderItemsErrors']->addError(new FormError(trans('admin.order.product_item_not_found')));
        }
    }

    public function associateOrderAndShipping(FormEvent $event)
    {
        /** @var RegularOrder $RegularOrder */
        $RegularOrder = $event->getData();
        $Items = $RegularOrder->getRegularOrderItems();

        // 明細とOrder, Shippingを紐付ける.
        // 新規の明細のみが対象, 更新時はスキップする.
        foreach ($Items as $Item) {
            // 更新時はスキップ
            if ($Item->getId()) {
                continue;
            }

            $Item->setRegularOrder($RegularOrder);

            // 送料明細の紐付けを行う.
            // 複数配送の場合は, 常に最初のShippingと紐付ける.
            // Order::getShippingsは氏名でソートされている.
            if ($Item->isDeliveryFee()) {
                $Item->setRegularShipping($RegularOrder->getRegularShippings()->first());
            }

            // 商品明細の紐付けを行う.
            // 複数配送時は, 明細の追加は行われないためスキップする.
            if ($Item->isProduct() && !$RegularOrder->isMultiple()) {
                $Item->setRegularShipping($RegularOrder->getRegularShippings()->first());
            }
        }
    }
}
