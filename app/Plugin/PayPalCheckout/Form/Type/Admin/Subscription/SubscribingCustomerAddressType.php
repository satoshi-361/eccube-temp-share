<?php

namespace Plugin\PayPalCheckout\Form\Type\Admin\Subscription;

use Eccube\Common\EccubeConfig;
use Plugin\PayPalCheckout\Entity\PrimaryShippingAddress;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class SubscribingCustomerAddressType
 * @package Plugin\PayPalCheckout\Form\Type\Admin\Subscription
 */
class SubscribingCustomerAddressType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    /**
     * ShippingAddressType constructor.
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
        $builder->add('first_name', TextType::class, [
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 255])
            ]
        ]);

        $builder->add('last_name', TextType::class, [
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 255])
            ]
        ]);

        $builder->add('first_name_kana', TextType::class, [
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 255])
            ]
        ]);

        $builder->add('last_name_kana', TextType::class, [
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 255])
            ]
        ]);

        $builder->add('postal_code', TextType::class, [
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 255])
            ]
        ]);

        $builder->add('pref', TextType::class, [
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 255])
            ]
        ]);

        $builder->add('address1', TextType::class, [
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 255])
            ]
        ]);

        $builder->add('address2', TextType::class, [
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 255])
            ]
        ]);

        $builder->add('phone_number', TextType::class, [
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 255])
            ]
        ]);

        $builder->add('company_name', TextType::class, [
            'constraints' => [
                new Assert\Length(['max' => 255])
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PrimaryShippingAddress::class
        ]);
    }
}
