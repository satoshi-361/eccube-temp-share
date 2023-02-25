<?php

namespace Plugin\CMBlogPro\Form\Type\Admin;

use Plugin\CMBlogPro\Entity\Blog;
use Plugin\CMBlogPro\Entity\Category;
use Plugin\CMBlogPro\Repository\BlogRepository;
use Plugin\CMBlogPro\Repository\CategoryRepository;
use Plugin\CMBlogPro\Form\Type\Admin\BlogStatusType;
use Plugin\CMBlogPro\Form\Validator\Hankaku;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Regex;

use Plugin\CMBlogPro\Repository\ProductRepository;
use Eccube\Entity\Product;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class BlogType extends AbstractType
{
    /**
     * @var BlogRepository
     */
    protected $blogRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * BlogType constructor.
     *
     * @param BlogRepository $blogRepository
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(
        BlogRepository $blogRepository,
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository
    ) {
        $this->blogRepository = $blogRepository;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository  = $productRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('title', TextType::class, [
            'required' => true,
            'constraints' => [
                new NotBlank(),
                new Length(['max' => 200]),
            ],
        ])
        ->add('slug', TextType::class, [
            'required' => false,
            'constraints' => [
                new Length(['max' => 255]),
                new Assert\Regex(array(
                    'pattern'   => "/[\/\:\!\"\'\#\$\%\&\(\)\=\~\^\.\<\>\|\*\;\{\}\+\?]+/",
                    'match'     => false,
                    "message"   => "記号は利用できません。"
                ))
            ],
        ])
        ->add('Category', ChoiceType::class, [
            'choice_label' => 'name',
            'multiple' => true,
            'mapped' => false,
            'expanded' => true,
            'choices' => $this->categoryRepository->getList(array()),
            'choice_value' => function (Category $Category = null) {
                return $Category ? $Category->getId() : null;
            },
        ])
        ->add('Product', ChoiceType::class, [
            'choice_label' => 'name',
            'multiple' => true,
            'mapped' => false,
            'expanded' => false,
            'choices' => $this->productRepository->getList(array()),
            'choice_value' => function (Product $Product = null) {
                return $Product ? $Product->getId() : null;
            },
        ])
        ->add('tag', TextType::class, [
            'required' => false,
            'attr'      => array(
                'placeholder' => '例：おすすめ,ビックアップ,注目,広告',
            ),
            'constraints' => [
                new Length(['max' => 1000]),
            ],
        ])
        ->add('product_image', FileType::class, [
            'multiple' => true,
            'required' => false,
            'mapped' => false,
        ])
        
        // ->add('main_image', TextType::class, [
        //     'required' => false,
        //     'mapped' => false,
        // ])
        // ->add('image', FileType::class, [
        //     'mapped' => false,
        //     'required' => false,
        //     'constraints' => [
        //         new File([
        //             'maxSize' => '1024k',
        //             'mimeTypes' => [
        //                 'image/jpeg',
        //                 'image/gif',
        //                 'image/png',
        //                 'image/tiff'
        //             ],
        //             'mimeTypesMessage' => '有効な画像をアップロードして下さい'
        //         ])
        //     ]
        // ])

        // 画像
        ->add('images', CollectionType::class, [
            'entry_type' => HiddenType::class,
            'prototype' => true,
            'mapped' => false,
            'allow_add' => true,
            'allow_delete' => true,
        ])
        ->add('add_images', CollectionType::class, [
            'entry_type' => HiddenType::class,
            'prototype' => true,
            'mapped' => false,
            'allow_add' => true,
            'allow_delete' => true,
        ])
        ->add('delete_images', CollectionType::class, [
            'entry_type' => HiddenType::class,
            'prototype' => true,
            'mapped' => false,
            'allow_add' => true,
            'allow_delete' => true,
        ])
        ->add('return_link', HiddenType::class, [
            'mapped' => false,
        ])
        ->add('body', TextareaType::class, [
            'attr' => [
                'rows' => 20,
            ],
            'required' => false,
        ])
        ->add('author', TextType::class, [
            'required' => false,
            'constraints' => [
                new Length(['max' => 255]),
            ],
        ])
        ->add('description', TextType::class, [
            'required' => false,
            'constraints' => [
                new Length(['max' => 255]),
            ],
        ])
        ->add('keyword', TextType::class, [
            'required' => false,
            'constraints' => [
                new Length(['max' => 255]),
            ],
        ])
        ->add('robot', TextType::class, [
            'required' => false,
            'constraints' => [
                new Length(['max' => 255]),
            ],
        ])
        ->add('metatag', TextareaType::class, [
            'required' => false,
        ])
        ->add('release_date', DateType::class, [
            'required'  => true,
            'widget'    => 'single_text',
            'attr'      => array(
                'placeholder' => 'yyyy-MM-dd',
            ),
            'constraints' => [
                new NotBlank(),
            ],
            'format'    => 'yyyy-MM-dd'
        ])
        ->add('status', BlogStatusType::class, [
            'constraints' => [
                new NotBlank(),
            ],
        ])
        ->add('is_for_restaurant', CheckboxType::class, [
            'label'    => '加盟店のみ限定',
            'required' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Blog::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'CMBlogPro_admin_blog';
    }
}
