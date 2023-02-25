<?php

namespace Plugin\CMBlogPro\Form\Type\Admin;

use Plugin\CMBlogPro\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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
            ->add('display_block', IntegerType::class)
            ->add('display_page', IntegerType::class)
            ->add('title_en', TextType::class, [
                'attr' => [
                    'placeholder' => 'plg.CMBlogPro.admin.config.title_en'
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 255]),
                ]
            ])
            ->add('title_jp', TextType::class, [
                'attr' => [
                    'placeholder' => 'plg.CMBlogPro.admin.config.title_jp'
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 255]),
                ]
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
