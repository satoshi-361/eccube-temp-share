<?php

namespace Plugin\EccubePaymentLite4\Form\Type\Admin;

use Doctrine\ORM\PersistentCollection;
use Eccube\Common\EccubeConfig;
use Eccube\Form\Validator\Email;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\ConvenienceStore;
use Plugin\EccubePaymentLite4\Entity\GmoEpsilonPayment;
use Plugin\EccubePaymentLite4\Entity\IpBlackList;
use Plugin\EccubePaymentLite4\Entity\MyPageRegularSetting;
use Plugin\EccubePaymentLite4\Entity\RegularCycle;
use Plugin\EccubePaymentLite4\Entity\RegularCycleType;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularStatus;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Repository\GmoEpsilonPaymentRepository;
use Plugin\EccubePaymentLite4\Repository\RegularOrderRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ConfigType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;
    /**
     * @var GmoEpsilonPaymentRepository
     */
    private $gmoEpsilonPaymentRepository;
    /**
     * @var RegularOrderRepository
     */
    private $regularOrderRepository;
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(
        EccubeConfig $eccubeConfig,
        GmoEpsilonPaymentRepository $gmoEpsilonPaymentRepository,
        RegularOrderRepository $regularOrderRepository,
        ConfigRepository $configRepository
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->gmoEpsilonPaymentRepository = $gmoEpsilonPaymentRepository;
        $this->regularOrderRepository = $regularOrderRepository;
        $this->configRepository = $configRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('contract_code', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ 契約コードが入力されていません。']),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_smtext_len']]),
                ],
            ])
            ->add('environmental_setting', ChoiceType::class, [
                'choices' => [
                    '開発' => Config::ENVIRONMENTAL_SETTING_DEVELOPMENT,
                    '本番' => Config::ENVIRONMENTAL_SETTING_PRODUCTION,
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => '環境設定は必須項目です。',
                    ]),
                ],
            ])
            ->add('credit_payment_setting', ChoiceType::class, [
                'choices' => [
                    'リンク決済' => Config::LINK_PAYMENT,
                    'トークン決済' => Config::TOKEN_PAYMENT,
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'クレジットカード決済設定は必須項目です。',
                    ]),
                ],
            ])
            ->add('gmo_epsilon_payments', EntityType::class, [
                'class' => GmoEpsilonPayment::class,
                'multiple' => true,
                'expanded' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ 利用決済方法が選択されていません。']),
                ],
            ])

            ->add('convenience_stores', EntityType::class, [
                'class' => ConvenienceStore::class,
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('card_expiration_notification_days', ChoiceType::class, [
                'choices' => [
                    '-' => null,
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5,
                    '6' => 6,
                    '7' => 7,
                    '8' => 8,
                    '9' => 9,
                    '10' => 10,
                    '11' => 11,
                    '12' => 12,
                    '13' => 13,
                    '14' => 14,
                    '15' => 15,
                    '16' => 16,
                    '17' => 17,
                    '18' => 18,
                    '19' => 19,
                    '20' => 20,
                    '21' => 21,
                    '22' => 22,
                    '23' => 23,
                    '24' => 24,
                    '25' => 25,
                ],
                'multiple' => false,
                'expanded' => false,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => '必須項目です。',
                    ]),
                ],
            ])
            ->add('regular', ChoiceType::class, [
                'choices' => [
                    '利用しない' => 0,
                    '利用する' => 1,
                ],
                'multiple' => false,
                'expanded' => true,
            ])
            ->add('regular_order_notification_email', EmailType::class, [
                'required' => false,
                'constraints' => [
                    new Email(['strict' => $this->eccubeConfig['eccube_rfc_email_check']]),
                ],
            ])
            ->add('my_page_regular_settings', EntityType::class, [
                'label' => 'gmo_epsilon.admin.config.my_page_regular_settings',
                'class' => MyPageRegularSetting::class,
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('next_delivery_date_changeable_range_days', ChoiceType::class, [
                'choices' => array_combine(range(1, 25), range(1, 25)),
                'empty_data' => 5,
                'multiple' => false,
                'expanded' => false,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('first_delivery_days', ChoiceType::class, [
                'choices' => array_combine(range(1, 25), range(1, 25)),
                'empty_data' => 5,
                'multiple' => false,
                'expanded' => false,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('next_delivery_days_at_regular_resumption', ChoiceType::class, [
                'choices' => array_combine(range(1, 25), range(1, 25)),
                'empty_data' => 5,
                'multiple' => false,
                'expanded' => false,
                'constraints' => [
                    new NotBlank(),
                    new Assert\Callback(function (
                        $object,
                        ExecutionContextInterface $context
                    ) {
                        /** @var Form $form */
                        $form = $context->getRoot();
                        /** @var Config $Config */
                        $Config = $this->configRepository->find(1);
                        $deadLine = $Config->getRegularOrderDeadline();
                        if ($object <= $deadLine && $form['regular']->getData()) {
                            $form['next_delivery_days_at_regular_resumption']->addError(new FormError('「定期受注注文締切日」よりも大きい日数を設定する必要があります。'));
                        }
                    }),
                ],
            ])
            ->add('next_delivery_days_after_re_payment', ChoiceType::class, [
                'label' => 'gmo_epsilon.admin.config.next_delivery_days_after_re_payment',
                'choices' => array_combine(range(1, 25), range(1, 25)),
                'empty_data' => 5,
                'multiple' => false,
                'expanded' => false,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('regular_order_deadline', ChoiceType::class, [
                'label' => 'gmo_epsilon.admin.config.regular_order_deadline',
                'choices' => array_combine(range(1, 25), range(1, 25)),
                'empty_data' => 5,
                'multiple' => false,
                'expanded' => false,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('regular_delivery_notification_email_days', ChoiceType::class, [
                'label' => 'gmo_epsilon.admin.config.regular_delivery_notification_email_days',
                'choices' => array_combine(range(1, 25), range(1, 25)),
                'empty_data' => 5,
                'multiple' => false,
                'expanded' => false,
            ])
            ->add('regular_stoppable_count', ChoiceType::class, [
                'label' => 'gmo_epsilon.admin.config.regular_stoppable_count',
                'choices' => array_combine(range(1, 10), range(1, 10)),
                'multiple' => false,
                'expanded' => false,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('regular_cancelable_count', ChoiceType::class, [
                'label' => 'gmo_epsilon.admin.config.regular_cancelable_count',
                'choices' => array_combine(range(1, 10), range(1, 10)),
                'multiple' => false,
                'expanded' => false,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('regular_resumable_period', NumberType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 1,
                    ]),
                    new Assert\Regex([
                        'pattern' => "/^\d+$/u",
                        'message' => 'form_error.numeric_only',
                    ]),
                ],
            ])
            ->add('regular_specified_count_notification_mail', NumberType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\GreaterThanOrEqual([
                        'value' => 2,
                        'message' => '※ 指定定期回数の通知は、２回目以降を設定してください。',
                    ]),
                    new Assert\Regex([
                        'pattern' => "/^\d+$/u",
                        'message' => 'form_error.numeric_only',
                    ]),
                ],
            ])
            ->add('regular_point_magnification', ChoiceType::class, [
                'label' => 'gmo_epsilon.admin.config.regular_point_magnification',
                'choices' => array_combine(range(0, 10), range(0, 10)),
                'multiple' => false,
                'expanded' => false,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('block_mode', ChoiceType::class, [
                'choices' => ['ON' => 1,
                    'OFF' => 0,
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ ブロックモードが選択されていません。']),
                ],
                'multiple' => false,
                'expanded' => true,
            ])

            ->add('access_frequency_time', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Callback(function (
                        $objcet,
                        ExecutionContextInterface $context
                    ) {
                        $block_mode = $context->getRoot()->get('block_mode')->getData();
                        if ($block_mode && !$objcet) {
                            $context->buildViolation('※ アクセス頻度が入力されていません。')
                                ->atPath('access_frequency_time')
                                ->addViolation();
                        }
                    }),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_smtext_len']]),
                ],
            ])

            ->add('access_frequency', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Callback(function (
                        $objcet,
                        ExecutionContextInterface $context
                    ) {
                        $block_mode = $context->getRoot()->get('block_mode')->getData();
                        if ($block_mode && !$objcet) {
                            $context->buildViolation('※ アクセス頻度が入力されていません。')
                                ->atPath('access_frequency')
                                ->addViolation();
                        }
                    }),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_smtext_len']]),
                ],
            ])

            ->add('block_time', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Callback(function (
                        $objcet,
                        ExecutionContextInterface $context
                    ) {
                        $block_mode = $context->getRoot()->get('block_mode')->getData();
                        if ($block_mode && !$objcet) {
                            $context->buildViolation('※ ブロック時間が入力されていません。')
                                ->atPath('block_time')
                                ->addViolation();
                        }
                    }),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_smtext_len']]),
                ],
            ])

            ->add('white_list', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Callback(function (
                        $objcet,
                        ExecutionContextInterface $context
                    ) {
                        if ($objcet) {
                            $ipAddresses = explode(',', $objcet);
                            foreach ($ipAddresses as $ip_address) {
                                if (!preg_match('/(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])/', $ip_address)) {
                                    $context->buildViolation('※ IPアドレスの形式が不正です。')
                                        ->atPath('white_list')
                                        ->addViolation();
                                }
                            }
                        }
                    }),
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_smtext_len']]),
                ],
            ])
            ->add('ip_black_list', CollectionType::class, [
                'required' => false,
                'entry_type' => IpBlackListType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
            ])
        ;
        $builder
            ->addEventListener(
                FormEvents::POST_SUBMIT, [
                    $this,
                    'validateNextDeliveryDaysAfterRePayment',
                ]
            )
            ->addEventListener(
                FormEvents::POST_SUBMIT, [
                    $this,
                    'validateRegularDeliveryNotificationEmailDays',
                ]
            )
            ->addEventListener(
                FormEvents::POST_SUBMIT, [
                    $this,
                    'validateRegularOrderNotificationEmail',
                ]
            )
            ->addEventListener(
                FormEvents::POST_SUBMIT, [
                    $this,
                    'validateRegularOrderDeadline',
                ]
            )
            ->addEventListener(
                FormEvents::POST_SUBMIT, [
                    $this,
                    'validateConvenienceStoreForm',
            ])
        ;
    }

    public function validateNextDeliveryDaysAfterRePayment(FormEvent $event)
    {
        /** @var Form $form */
        $form = $event->getForm();
        $nextDeliveryDaysAfterRePayment = $form['next_delivery_days_after_re_payment']->getData();
        $deadline = $form['regular_order_deadline']->getData();
        if ($deadline < $nextDeliveryDaysAfterRePayment || !$form['regular']->getData()) {
            return;
        }
        $form['next_delivery_days_after_re_payment']
            ->addError(
                new FormError(
                    '「再決済後の次回配送日数」は「定期受注注文締切日」よりも大きい値を設定する必要があります。'
                )
            );
    }

    public function validateRegularDeliveryNotificationEmailDays(FormEvent $event)
    {
        /** @var Form $form */
        $form = $event->getForm();
        $regularDeliveryNotificationEmailDays = $form['regular_delivery_notification_email_days']->getData();
        $deadline = $form['regular_order_deadline']->getData();
        if ($deadline < $regularDeliveryNotificationEmailDays || !$form['regular']->getData()) {
            return;
        }
        $form['regular_delivery_notification_email_days']
            ->addError(
                new FormError(
                    '「定期配送事前お知らせメール配信日数」は「定期受注注文締切日」よりも大きい値を設定する必要があります。'
                )
            );
    }

    public function validateRegularOrderNotificationEmail(FormEvent $event)
    {
        /** @var Form $form */
        $form = $event->getForm();
        if (is_null($form['regular_order_notification_email']->getData()) &&
            $form['regular']->getData()) {
            $form['regular_order_notification_email']
                ->addError(new FormError('定期受注作成時通知メールアドレスは必須入力です。'));
        }
    }

    public function validateRegularOrderDeadline(FormEvent $event)
    {
        /** @var Form $form */
        $form = $event->getForm();
        $deadline = $form['regular_order_deadline']->getData();
        /** @var RegularOrder[] $RegularOrders */
        $RegularOrders = $this->regularOrderRepository->findBy([
            'RegularStatus' => [
                RegularStatus::CONTINUE,
                RegularStatus::SUSPEND,
                RegularStatus::PAYMENT_ERROR,
                RegularStatus::SYSTEM_ERROR,
                RegularStatus::WAITING_RE_PAYMENT,
            ],
        ]);
        // 定期サイクルが日ごと、または週ごとの受注がチェック対象
        foreach ($RegularOrders as $RegularOrder) {
            /** @var RegularCycle $RegularCycle */
            $RegularCycle = $RegularOrder->getRegularCycle();
            $regularCycleTypeId = $RegularCycle->getRegularCycleType()->getId();
            $cycleDays = 30;
            if ($regularCycleTypeId === RegularCycleType::REGULAR_DAILY_CYCLE) {
                $cycleDays = $RegularCycle->getDay();
            }
            if ($regularCycleTypeId === RegularCycleType::REGULAR_WEEKLY_CYCLE) {
                $cycleDays = $RegularCycle->getDay() * 7;
            }
            if ($cycleDays <= $deadline && $form['regular']->getData()) {
                $form['regular_order_deadline']->addError(new FormError('定期受注注文締切日は、定期ステータスが「解約」以外の定期受注の定期サイクル周期日数よりも少ない日数を入力する必要があります。'));

                return;
            }
        }
    }

    public function validateConvenienceStoreForm(FormEvent $event)
    {
        /** @var GmoEpsilonPayment $GmoEpsilonPayment */
        $GmoEpsilonPayment = $this->gmoEpsilonPaymentRepository->find(GmoEpsilonPayment::CONVENIENCE_STORE);
        /** @var Form $form */
        $form = $event->getForm();
        $form['gmo_epsilon_payments']->getData();
        if ($form['gmo_epsilon_payments']->getData()->contains($GmoEpsilonPayment)) {
            /** @var PersistentCollection $convenienceStores */
            $convenienceStores = $form['convenience_stores']->getData();
            if ($convenienceStores->count() >= 1) {
                return;
            }
            $form['convenience_stores']->addError(new FormError('コンビニ種別は必須入力です。'));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
        ]);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        usort($view['ip_black_list']->children, function (FormView $a, FormView $b) {
            /** @var IpBlackList $objectA */
            $objectA = $a->vars['data'];
            /** @var IpBlackList $objectB */
            $objectB = $b->vars['data'];

            return $objectA->getSortNo() < $objectB->getSortNo() ? -1 : 1;
        });
    }
}
