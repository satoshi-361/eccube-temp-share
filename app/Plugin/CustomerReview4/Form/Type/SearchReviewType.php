<?php

namespace Plugin\CustomerReview4\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchReviewType extends AbstractType
{
    /**
     * build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('pageno', HiddenType::class, []);
        $builder->add('star', HiddenType::class, []);
        $builder->add('orderby', HiddenType::class, []);
    }

    /**
     * Config.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
    }

    /**
     * block prefix.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'search_review';
    }

}