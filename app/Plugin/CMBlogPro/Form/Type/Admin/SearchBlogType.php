<?php

namespace Plugin\CMBlogPro\Form\Type\Admin;

use Plugin\CMBlogPro\Entity\Category;
use Plugin\CMBlogPro\Entity\BlogStatus;

use Plugin\CMBlogPro\Repository\BlogStatusRepository;
use Plugin\CMBlogPro\Repository\CategoryRepository;

use Eccube\Form\Type\Master\CategoryType as MasterCategoryType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;


class SearchBlogType extends AbstractType
{
    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var BlogStatusRepository
     */
    protected $blogStatusRepository;

    /**
     * BlogType constructor.
     *
     * @param CategoryRepository $categoryRepository
     * @param BlogStatusRepository $blogStatusRepository
     * 
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        BlogStatusRepository $blogStatusRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->blogStatusRepository = $blogStatusRepository;

    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', TextType::class, [
                'label' => 'plg.CMBlogPro.admin.blog.multi_search_label',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 100]),
                ],
            ])
            // ->add('categories', ChoiceType::class, [
            //     'choice_label' => 'name',
            //     'multiple' => true,
            //     'mapped' => false,
            //     'expanded' => true,
            //     'choices' => $this->categoryRepository->getList(array()),
            //     'choice_value' => function (Category $Category = null) {
            //         return $Category ? $Category->getId() : null;
            //     },
            // ])
            ->add('category_id', MasterCategoryType::class, [
                //'choice_label' => 'NameWithLevel',
                'choice_label' => 'name',
                'label' => 'plg.CMBlogPro.category.category',
                'placeholder' => 'plg.CMBlogPro.category.select_all_categories',
                'required' => false,
                'multiple' => false,
                'expanded' => false,
                'choices' => $this->categoryRepository->getList(null, true),
                'choice_value' => function (Category $Category = null) {
                    return $Category ? $Category->getId() : null;
                },
            ])
            ->add('status', BlogStatusType::class, [
                'label' => 'plg.CMBlogPro.blog.display_status',
                'multiple' => true,
                'required' => false,
                'expanded' => true,
                'data' => $this->blogStatusRepository->findBy(['id' => [
                    BlogStatus::DISPLAY_SHOW,
                    BlogStatus::DISPLAY_HIDE,
                ]]),
            ])
            ->add('create_date_start', DateType::class, [
                'label' => 'admin.common.create_date__start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr' => [
                    'placeholder' => 'yyyy-MM-dd',
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_create_date_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('create_date_end', DateType::class, [
                'label' => 'admin.common.create_date__end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr' => [
                    'placeholder' => 'yyyy-MM-dd',
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_create_date_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('update_date_start', DateType::class, [
                'label' => 'admin.common.update_date__start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr' => [
                    'placeholder' => 'yyyy-MM-dd',
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_update_date_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('update_date_end', DateType::class, [
                'label' => 'admin.common.update_date__end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr' => [
                    'placeholder' => 'yyyy-MM-dd',
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_update_date_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('releaseStart', DateType::class, [
                'label' => 'plg.CMBlogPro.admin.blog.release_date__start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr' => [
                    'placeholder' => 'yyyy-MM-dd',
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'releaseStart',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('releaseEnd', DateType::class, [
                'label' => 'plg.CMBlogPro.admin.blog.release_date__end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr' => [
                    'placeholder' => 'yyyy-MM-dd',
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'releaseEnd',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
        ;
    }
}
