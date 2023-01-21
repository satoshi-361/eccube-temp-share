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

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Customer;
use Eccube\Form\Type\NameType;
use Eccube\Form\Type\PhoneNumberType;
use Eccube\Form\Type\RepeatedPasswordType;
use Eccube\Form\Type\Master\CustomerStatusType;
use Eccube\Form\Validator\Email;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;

use Eccube\Form\Type\Admin\CustomerType as BaseType;

class CustomerType extends BaseType
{
    public function __construct(EccubeConfig $eccubeConfig)
    {
        parent::__construct($eccubeConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', NameType::class, [
                'required' => true,
            ])
            ->add('nick_name', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('paypal_email', EmailType::class, [
                'required' => false,
                'constraints' => [
                    new Email(['strict' => $this->eccubeConfig['eccube_rfc_email_check']]),
                ],
                'attr' => [
                    'placeholder' => 'common.mail_address_sample',
                ],
            ])
            ->add('phone_number', PhoneNumberType::class, [
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Email(['strict' => $this->eccubeConfig['eccube_rfc_email_check']]),
                ],
                'attr' => [
                    'placeholder' => 'common.mail_address_sample',
                ],
            ])
            ->add('password', RepeatedPasswordType::class, [
                // 'type' => 'password',
                'first_options' => [
                    'label' => 'member.label.pass',
                ],
                'second_options' => [
                    'label' => 'member.label.verify_pass',
                ],
            ])
            ->add('status', CustomerStatusType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('note', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_ltext_len'],
                    ]),
                ],
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            /** @var Customer $Customer */
            $Customer = $event->getData();
            if ($Customer->getPassword() != '' && $Customer->getPassword() == $Customer->getEmail()) {
                $form['password']['first']->addError(new FormError(trans('common.password_eq_email')));
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $Customer = $event->getData();

            // ポイント数が入力されていない場合0を登録
            if (is_null($Customer->getPoint())) {
                $Customer->setPoint(0);
            }
        });
    }
}
