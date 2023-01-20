<?php

namespace Plugin\CustomerReview4\Form\Type\Admin;

use Plugin\CustomerReview4\Entity\CustomerReviewStatus;
use Plugin\CustomerReview4\Repository\CustomerReviewStatusRepository;
use Eccube\Common\EccubeConfig;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SearchReviewType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var CustomerReviewStatusRepository
     */
    protected $customerReviewStatusRepository;

    /**
     * ProductReviewSearchType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     * @param CustomerReviewStatusRepository $customerReviewStatusRepository
     */
    public function __construct(EccubeConfig $eccubeConfig, CustomerReviewStatusRepository $customerReviewStatusRepository)
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->customerReviewStatusRepository = $customerReviewStatusRepository;
    }

    /**
     * build form.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $config = $this->eccubeConfig;
        $builder
            ->add('status', EntityType::class, [
                'class' => CustomerReviewStatus::class,
                'label' => 'customer_review4.admin.review_list.search_review_status',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'data' => $this->customerReviewStatusRepository->findBy( ['id' => [ CustomerReviewStatus::POST ]] ),
            ])
            ->add('create_date_start', DateType::class, [
                'label' => 'customer_review4.admin.review_list.search_posted_date_start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_create_date_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('create_date_end', DateType::class, [
                'label' => 'customer_review4.admin.review_list.search_posted_date_end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_create_date_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('product_id', TextType::class, [
                'label' => 'customer_review4.admin.review_list.search_product_id',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $config['eccube_stext_len']]),
                ],
            ])
            ->add('customer_id', IntegerType::class, [
                'label' => 'customer_review4.admin.review_list.customer_id',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $config['eccube_stext_len']]),
                ],
            ]);
    }

    /**
     * block prefix.
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'admin_search_review';
    }
}
