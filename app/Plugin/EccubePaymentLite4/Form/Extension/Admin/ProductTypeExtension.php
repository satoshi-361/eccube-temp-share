<?php

namespace Plugin\EccubePaymentLite4\Form\Extension\Admin;

use Doctrine\ORM\EntityRepository;
use Eccube\Entity\Category;
use Eccube\Entity\Delivery;
use Eccube\Entity\Master\SaleType;
use Eccube\Entity\Payment;
use Eccube\Form\Type\Admin\ProductClassType;
use Eccube\Form\Type\Admin\ProductType;
use Eccube\Form\Type\Master\PaymentType;
use Eccube\Form\Type\Master\ProductStatusType;
use Eccube\Form\Validator\TwigLint;
use Eccube\Repository\Master\SaleTypeRepository;
use Eccube\Service\Payment\Method\Cash;
use Plugin\EccubePaymentLite4\Entity\DeliveryCompany;
use Plugin\EccubePaymentLite4\Entity\RegularCycle;
use Plugin\EccubePaymentLite4\Service\Method\Credit;
use Plugin\EccubePaymentLite4\Service\Method\Reg_Credit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProductTypeExtension extends AbstractTypeExtension
{
    /**
     * @var SaleTypeRepository
     */
    private $saleTypeRepository;

    public function __construct(
        SaleTypeRepository $saleTypeRepository
    ) {
        $this->saleTypeRepository = $saleTypeRepository;
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // 分量に関するフリー記述
            ->add('free_description_about_quantity', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new TwigLint(),
                ],
            ])
            // 販売価格に関するフリー記述
            ->add('free_description_about_selling_price', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new TwigLint(),
                ],
            ])
            // お支払い・引き渡しに関するフリー記述
            ->add('free_description_of_payment_delivery', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new TwigLint(),
                ],
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductType::class;
    }

    /**
     * Return the class of the type being extended.
     */
    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }

}
