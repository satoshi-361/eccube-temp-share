<?php

namespace Plugin\EccubePaymentLite4\Form\Type\Front;

use Eccube\Form\Type\AddressType;
use Eccube\Form\Type\KanaType;
use Eccube\Form\Type\NameType;
use Eccube\Form\Type\PhoneNumberType;
use Eccube\Form\Type\PostalType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * 定期お届け先変更用Form.
 */
class RegularShippingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $form = $event->getForm();
                $form
                    ->add('name', NameType::class, [
                        'required' => true,
                    ])
                    ->add('kana', KanaType::class, [
                        'required' => true,
                    ])
                    ->add('company_name', TextType::class, [
                        'required' => false,
                        'constraints' => [
                            new Assert\Length([
                                'max' => 255,
                            ]),
                        ],
                    ])
                    ->add('postal_code', PostalType::class)
                    ->add('address', AddressType::class)
                    ->add('phone_number', PhoneNumberType::class, [
                        'required' => true,
                    ])
                ;
            });
    }
}
