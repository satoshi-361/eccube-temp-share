<?php

namespace Plugin\PayPalCheckout\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Plugin\PayPalCheckout\Entity\PluginSetting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ConfigType
 * @package Plugin\PayPalCheckout\Form\Type\Admin
 */
class ConfigType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * ConfigType constructor.
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('client_id', TextType::class, [
            'label' => 'クライアントID',
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 255])
            ]
        ]);

        $builder->add('client_secret', TextType::class, [
            'label' => 'クライアントシークレット',
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 255])
            ]
        ]);

        $builder->add('reference_day', TextType::class, [
            'label' => '基準日',
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 255])
            ]
        ]);

        $builder->add('cut_off_day', TextType::class, [
            'label' => '締日',
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 255])
            ]
        ]);

        $builder->add('use_express_btn', CheckboxType::class, [
            'label' => 'カート画面で「PayPal」ボタンを使用する',
            'required' => false
        ]);

        $builder->add('use_sandbox', CheckboxType::class, [
            'label' => 'Sandbox(開発用テストツール)の使用',
            'required' => false
        ]);

        $builder->add('use_vault', CheckboxType::class, [
            'label' => 'カード情報保存機能の使用',
            'required' => false
        ]);

        $builder->add('use_3dsecure', CheckboxType::class, [
            'label' => '3Dセキュアの使用',
            'required' => false
        ]);

        $builder->add('paypal_logo', ChoiceType::class, [
            'label' => 'PayPalロゴ',
            'choices' => [
                $this->eccubeConfig->get('paypal.paypal_express_paypal_logo_1') => '1',
                $this->eccubeConfig->get('paypal.paypal_express_paypal_logo_2') => '2',
                $this->eccubeConfig->get('paypal.paypal_express_paypal_logo_3') => '3',
            ],
            'expanded' => true,
            'multiple' => false
        ]);

        $builder->add('payment_paypal_logo', ChoiceType::class, [
            'label' => 'PayPalロゴ',
            'choices' => [
                $this->eccubeConfig->get('paypal.paypal_express_payment_paypal_logo_1') => '1',
                $this->eccubeConfig->get('paypal.paypal_express_payment_paypal_logo_2') => '2',
                $this->eccubeConfig->get('paypal.paypal_express_payment_paypal_logo_3') => '3',
            ],
            'expanded' => true,
            'multiple' => false
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PluginSetting::class,
        ]);
    }
}
