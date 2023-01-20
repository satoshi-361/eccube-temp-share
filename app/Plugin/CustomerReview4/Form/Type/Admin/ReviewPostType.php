<?php

namespace Plugin\CustomerReview4\Form\Type\Admin;


use Plugin\CustomerReview4\Entity\CustomerReviewList;
use Plugin\CustomerReview4\Entity\CustomerReviewStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ReviewPostType extends AbstractType
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
          ->add('Status', EntityType::class, [
              'class' => CustomerReviewStatus::class,
              'constraints' => [
                  new Assert\NotBlank(),
              ],
          ])
          ->add('reviewer_name', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
          ])
          ->add('recommend_level', ChoiceType::class, [
                'choices' => [
                    '★★★★★' => 5, 
                    '★★★★' => 4, 
                    '★★★' => 3, 
                    '★★' => 2, 
                    '★' => 1, 
                ],
          ])
          ->add('title', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
          ])
          ->add('comment', TextareaType::class, [
                'required' => true,
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
            'data_class' => CustomerReviewList::class,
        ]);
    }

}