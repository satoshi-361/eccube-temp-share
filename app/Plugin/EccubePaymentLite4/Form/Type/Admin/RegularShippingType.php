<?php

namespace Plugin\EccubePaymentLite4\Form\Type\Admin;

use Doctrine\ORM\EntityRepository;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Delivery;
use Eccube\Entity\DeliveryTime;
use Eccube\Entity\Shipping;
use Eccube\Form\Type\AddressType;
use Eccube\Form\Type\KanaType;
use Eccube\Form\Type\NameType;
use Eccube\Form\Type\PhoneNumberType;
use Eccube\Form\Type\PostalType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\DeliveryRepository;
use Eccube\Repository\DeliveryTimeRepository;
use Eccube\Util\StringUtil;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\RegularOrderItem;
use Plugin\EccubePaymentLite4\Entity\RegularShipping;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RegularShippingType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var DeliveryRepository
     */
    protected $deliveryRepository;

    /**
     * @var DeliveryTimeRepository
     */
    protected $deliveryTimeRepository;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(
        EccubeConfig $eccubeConfig,
        DeliveryRepository $deliveryRepository,
        DeliveryTimeRepository $deliveryTimeRepository,
        BaseInfoRepository $baseInfoRepository,
        ConfigRepository $configRepository
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->deliveryRepository = $deliveryRepository;
        $this->deliveryTimeRepository = $deliveryTimeRepository;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->configRepository = $configRepository;
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
                'required' => true,
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
                    'attr' => [
                        'class' => 'p-locality p-street-address',
                        'placeholder' => 'admin.common.address_sample_01',
                    ],
                ],
                'addr02_options' => [
                    'required' => false,
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length([
                            'max' => $this->eccubeConfig['eccube_mtext_len'],
                        ]),
                    ],
                    'attr' => [
                        'class' => 'p-extended-address',
                        'placeholder' => 'admin.common.address_sample_02',
                    ],
                ],
            ])
            ->add('phone_number', PhoneNumberType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('Delivery', EntityType::class, [
                'required' => false,
                'class' => Delivery::class,
                'choice_label' => function (Delivery $Delivery) {
                    return $Delivery->isVisible()
                        ? $Delivery->getServiceName()
                        : $Delivery->getServiceName().trans('admin.common.hidden_label');
                },
                'query_builder' => function ($er) {
                    return $er->createQueryBuilder('d')
                        ->orderBy('d.visible', 'DESC') // 非表示は下に配置
                        ->addOrderBy('d.sort_no', 'ASC');
                },
                'placeholder' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('next_delivery_date', DateType::class, [
                'placeholder' => '',
                'format' => 'yyyy-MM-dd',
                'required' => false,
                'constraints' => [
                    new Assert\Callback(function (
                        $object,
                        ExecutionContextInterface $context
                    ) {
                        /** @var Config $Config */
                        $Config = $this->configRepository->find(1);
                        $deadlineDate = new \DateTime('today');
                        $deadlineDate->modify('+'.$Config->getRegularOrderDeadline().' day');
                        if (is_null($deadlineDate)) {
                            return;
                        }
                    }),
                ],
            ])
            ->add('RegularOrderItems', CollectionType::class, [
                'entry_type' => RegularOrderItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
            ])
            // 明細業のエラー表示用
            ->add('OrderItemsErrors', TextType::class, [
                'mapped' => false,
            ])
            ->add('notify_email', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'data' => true,
            ])
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
                /** @var Shipping $data */
                $data = $event->getData();
                /** @var Form $form */
                $form = $event->getForm();

                if (!$data) {
                    return;
                }

                $Delivery = $data->getDelivery();
                $timeId = $data->getTimeId();
                $DeliveryTime = null;
                if ($timeId) {
                    $DeliveryTime = $this->deliveryTimeRepository->find($timeId);
                }

                // お届け時間を配送業者で絞り込み
                $form->add('DeliveryTime', EntityType::class, [
                    'class' => DeliveryTime::class,
                    'choice_label' => function (DeliveryTime $DeliveryTime) {
                        return $DeliveryTime->isVisible()
                            ? $DeliveryTime->getDeliveryTime()
                            : $DeliveryTime->getDeliveryTime().trans('admin.common.hidden_label');
                    },
                    'placeholder' => 'common.select__unspecified',
                    'required' => false,
                    'data' => $DeliveryTime,
                    'query_builder' => function (EntityRepository $er) use ($Delivery) {
                        $qb = $er->createQueryBuilder('dt');
                        $qb
                            ->orderBy('dt.visible', 'DESC') // 非表示は下に配置
                            ->addOrderBy('dt.sort_no', 'ASC');
                        if ($Delivery) {
                            $qb
                                ->where('dt.Delivery = :Delivery')
                                ->setParameter('Delivery', $Delivery);
                        }

                        return $qb;
                    },
                    'mapped' => false,
                ]);
            })
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();

                if (!$data) {
                    return;
                }

                $Delivery = null;
                if (StringUtil::isNotBlank($data['Delivery'])) {
                    $Delivery = $this->deliveryRepository->find($data['Delivery']);
                }

                // お届け時間を配送業者で絞り込み
                $form->remove('DeliveryTime');
                $form->add('DeliveryTime', EntityType::class, [
                    'class' => DeliveryTime::class,
                    'choice_label' => 'delivery_time',
                    'placeholder' => 'common.select__unspecified',
                    'required' => false,
                    'query_builder' => function (EntityRepository $er) use ($Delivery) {
                        $qb = $er->createQueryBuilder('dt');
                        if ($Delivery) {
                            $qb
                                ->where('dt.Delivery = :Delivery')
                                ->setParameter('Delivery', $Delivery);
                        }

                        return $qb;
                    },
                    'mapped' => false,
                ]);
            })
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $Shipping = $event->getData();
                $Delivery = $Shipping->getDelivery();
                $Shipping->setShippingDeliveryName($Delivery ? $Delivery->getName() : null);
                $DeliveryTime = $form['DeliveryTime']->getData();
                if ($DeliveryTime) {
                    $Shipping->setShippingDeliveryTime($DeliveryTime->getDeliveryTime());
                    $Shipping->setTimeId($DeliveryTime->getId());
                } else {
                    $Shipping->setShippingDeliveryTime(null);
                    $Shipping->setTimeId(null);
                }
            })
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                // 出荷編集画面のみバリデーションをする。
                if ($event->getForm()->getParent()->getName() != 'shippings') {
                    return;
                }

                /** @var RegularShipping $RegularShipping */
                $RegularShipping = $event->getData();
                $RegularOrderItems = $RegularShipping->getRegularOrderItems();

                $count = 0;
                /** @var RegularOrderItem $Item */
                foreach ($RegularOrderItems as $Item) {
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
            });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RegularShipping::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'regular_shipping';
    }
}
