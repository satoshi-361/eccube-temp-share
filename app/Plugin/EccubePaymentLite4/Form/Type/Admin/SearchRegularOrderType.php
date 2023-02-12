<?php

namespace Plugin\EccubePaymentLite4\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Eccube\Form\EventListener\ConvertKanaListener;
use Eccube\Form\Type\Master\OrderStatusType;
use Eccube\Form\Type\Master\SexType;
use Eccube\Form\Type\PriceType;
use Plugin\EccubePaymentLite4\Entity\RegularShipping;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SearchRegularOrderType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // 定期ID・注文者名・注文者（フリガナ）・注文者会社名
            ->add('multi', TextType::class, [
                'label' => 'admin.order.multi_search_label',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                ],
            ])
            ->add('latest_status', OrderStatusType::class, [
                'label' => 'gmo_epsilon.admin.regular_order.latest_status',
                'expanded' => false,
                'multiple' => false,
                'placeholder' => '指定なし',
            ])
            ->add('regular_status', RegularStatusType::class, [
                'label' => 'gmo_epsilon.admin.regular_order.regular_status',
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('name', TextType::class, [
                'label' => 'admin.order.orderer_name',
                'required' => false,
            ])
            ->add($builder
                ->create('kana', TextType::class, [
                    'label' => 'admin.order.orderer_kana',
                    'required' => false,
                    'constraints' => [
                        new Assert\Regex([
                            'pattern' => '/^[ァ-ヶｦ-ﾟー]+$/u',
                            'message' => 'form_error.kana_only',
                        ]),
                    ],
                ])
                ->addEventSubscriber(new ConvertKanaListener('CV')
            ))
            ->add('company_name', TextType::class, [
                'label' => 'admin.order.orderer_company_name',
                'required' => false,
            ])
            ->add('email', TextType::class, [
                'label' => 'admin.common.mail_address',
                'required' => false,
            ])
            ->add('phone_number', TextType::class, [
                'label' => 'admin.common.phone_number',
                'required' => false,
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => "/^[\d-]+$/u",
                        'message' => 'form_error.graph_and_hyphen_only',
                    ]),
                ],
            ])
            ->add('sex', SexType::class, [
                'label' => 'admin.common.gender',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('first_order_date_start', DateType::class, [
                'label' => 'gmo_epsilon.admin.regular_order.first_order_date_start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_first_order_date_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('first_order_date_end', DateType::class, [
                'label' => 'gmo_epsilon.admin.regular_order.first_order_date_end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_first_order_date_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('next_delivery_date_start', DateType::class, [
                'label' => 'gmo_epsilon.admin.regular_order.next_delivery_date_start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_next_delivery_date_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('next_delivery_date_end', DateType::class, [
                'label' => 'gmo_epsilon.admin.regular_order.next_delivery_date_end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_next_delivery_date_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('regular_order_id', NumberType::class, [
                'label' => 'gmo_epsilon.admin.regular_order.regular_order_id',
                'required' => false,
            ])
            ->add('buy_product_id', NumberType::class, [
                'label' => 'gmo_epsilon.admin.regular_order.buy_product_id',
                'required' => false,
            ])
            ->add('order_no', NumberType::class, [
                'label' => 'gmo_epsilon.admin.regular_order.order_no',
                'required' => false,
            ])
            ->add('buy_product_name', TextType::class, [
                'label' => 'gmo_epsilon.admin.regular_order.buy_product_name',
                'required' => false,
            ])
            ->add('regular_count_start', NumberType::class, [
                'label' => 'gmo_epsilon.admin.regular_order.regular_count_start',
                'required' => false,
            ])
            ->add('regular_count_end', NumberType::class, [
                'label' => 'gmo_epsilon.admin.regular_order.regular_count_end',
                'required' => false,
            ])
            ->add('payment_total_start', PriceType::class, [
                'label' => 'admin.order.purchase_price__start',
                'required' => false,
            ])
            ->add('payment_total_end', PriceType::class, [
                'label' => 'admin.order.purchase_price__end',
                'required' => false,
            ])
            ->add('card_change_request_mail_status', ChoiceType::class, [
                'label' => 'gmo_epsilon.admin.regular_order.card_change_request_mail_status',
                'choices' => [
                    '未送信' => RegularShipping::CARD_CHANGE_REQUEST_MAIL_UNSENT,
                    '送信済み' => RegularShipping::CARD_CHANGE_REQUEST_MAIL_SENT,
                ],
                'expanded' => true,
                'multiple' => true,
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'admin_search_regular_order';
    }
}
