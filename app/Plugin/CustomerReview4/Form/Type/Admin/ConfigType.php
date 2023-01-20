<?php

namespace Plugin\CustomerReview4\Form\Type\Admin;

use Plugin\CustomerReview4\Entity\CustomerReviewConfig;
use Eccube\Form\Type\ToggleSwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ConfigType extends AbstractType
{
    /**
     * build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          ->add('review_max', ChoiceType::class, [
                'choices' => [
                    '全て' => 0, 
                    '10件' => 10, 
                    '20件' => 20, 
                    '30件' => 30, 
                    '40件' => 40, 
                    '50件' => 50, 
                    '60件' => 60, 
                    '70件' => 70, 
                    '80件' => 80, 
                    '90件' => 90, 
                    '100件' => 100
                ],
          ])
          ->add('grant_point', IntegerType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                ],
          ])
          ->add('grant_point_purchase', ToggleSwitchType::class )
          ->add('login_only', ToggleSwitchType::class )
          ->add('detail_in_review', ToggleSwitchType::class )
          ->add('purchase_mark', ToggleSwitchType::class )
          ->add('default_reviewer_name', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                ],
          ]);
    }

    /**
     * Config.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CustomerReviewConfig::class,
        ]);
    }
}
