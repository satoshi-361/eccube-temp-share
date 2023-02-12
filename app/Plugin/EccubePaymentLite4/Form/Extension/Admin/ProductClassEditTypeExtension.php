<?php

namespace Plugin\EccubePaymentLite4\Form\Extension\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\ProductClass;
use Eccube\Form\Type\Admin\ProductClassEditType;
use Plugin\EccubePaymentLite4\Entity\ProductClassRegularCycle;
use Plugin\EccubePaymentLite4\Entity\RegularCycle;
use Plugin\EccubePaymentLite4\Repository\RegularDiscountRepository;
use Plugin\EccubePaymentLite4\Service\IsExistRegularOrderWithRegularCyclesToBeDeleted;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductClassEditTypeExtension extends AbstractTypeExtension
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var IsExistRegularOrderWithRegularCyclesToBeDeleted
     */
    private $isExistRegularOrderWithRegularCyclesToBeDeleted;
    /**
     * @var RegularDiscountRepository
     */
    private $regularDiscountRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        IsExistRegularOrderWithRegularCyclesToBeDeleted $isExistRegularOrderWithRegularCyclesToBeDeleted,
        RegularDiscountRepository $regularDiscountRepository
    ) {
        $this->entityManager = $entityManager;
        $this->isExistRegularOrderWithRegularCyclesToBeDeleted = $isExistRegularOrderWithRegularCyclesToBeDeleted;
        $this->regularDiscountRepository = $regularDiscountRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ProductClassEditType::class;
    }

    /**
     * Return the class of the type being extended.
     */
    public static function getExtendedTypes(): iterable
    {
        return [ProductClassEditType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $discountGroups = $this->regularDiscountRepository->getRegularDiscountsChoices();
        $builder->add('RegularDiscount', ChoiceType::class, [
            'choices' => array_flip($discountGroups),
            'required' => false,
            'placeholder' => '選択してください',
            'mapped' => false,
        ]);

        $builder
            ->addEventListener(
                FormEvents::POST_SET_DATA,
                [$this, 'setRegularDiscountData']
            )
            ->addEventListener(
                FormEvents::POST_SET_DATA,
                [$this, 'setRegularCycleData']
            )
            ->addEventListener(
                FormEvents::POST_SUBMIT,
                [$this, 'validateProductClassRegularCycle']
            )
            ->addEventListener(
            FormEvents::POST_SUBMIT,
            [$this, 'setProductClassRegularCycle']
            )
            ->addEventListener(
                FormEvents::POST_SUBMIT,
                [$this, 'setRegularDiscount']
            )
        ;
    }

    public function setRegularDiscountData(FormEvent $event)
    {
        /** @var Form $form */
        $form = $event->getForm();
        /** @var ProductClass $ProductClass */
        $ProductClass = $event->getData();
        if (is_null($ProductClass)) {
            return;
        }

        if (!empty($ProductClass)) {
            $form['RegularDiscount']->setData($ProductClass->getRegularDiscount());
        }
    }

    public function setRegularCycleData(FormEvent $event)
    {
        /** @var Form $form */
        $form = $event->getForm();
        /** @var ProductClass $ProductClass */
        $ProductClass = $event->getData();
        $form
            ->add('RegularCycle', EntityType::class, [
                'class' => RegularCycle::class,
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'mapped' => false,
                'query_builder' => function ($er) {
                    return $er->createQueryBuilder('rc')
                        ->orderBy('rc.sort_no', 'DESC');
                },
            ])
        ;
        if (!empty($ProductClass)) {
            $form['RegularCycle']->setData($ProductClass->getRegularCycle());
        }
    }

    public function validateProductClassRegularCycle(FormEvent $event)
    {
        /** @var Form $form */
        $form = $event->getForm();
        if ($form['sale_type']->getData()->getName() === '定期商品' && empty($form['RegularCycle']->getData())) {
            $form['RegularCycle']->addError(new FormError('定期サイクルは必須入力です。'));
        }
        if ($this->isExistRegularOrderWithRegularCyclesToBeDeleted->isExist(
            $event->getData()->getRegularCycle(),
            $form['RegularCycle']->getData()
        )) {
            $form['RegularCycle']->addError(new FormError('「解約」以外の定期ステータスの定期受注が存在するため、定期サイクルを解除出来ません。'));
        }
    }

    public function setProductClassRegularCycle(FormEvent $event)
    {
        /** @var ProductClass $ProductClass */
        $ProductClass = $event->getData();
        /** @var Form $form */
        $form = $event->getForm();
        foreach ($ProductClass->getProductClassRegularCycle() as $ProductClassRegularCycle) {
            $ProductClass->removeProductClassRegularCycle($ProductClassRegularCycle);
            $this->entityManager->remove($ProductClassRegularCycle);
        }
        /** @var RegularCycle[] $RegularCycles */
        $RegularCycles = $form['RegularCycle']->getData();
        foreach ($RegularCycles as $RegularCycle) {
            /* @var RegularCycle $RegularCycle */
            if ($ProductClass->getSaleType()->getName() !== '定期商品') {
                continue;
            }
            $ProductClassRegularCycle = new ProductClassRegularCycle();
            $ProductClassRegularCycle
                ->setProductClass($ProductClass)
                ->setRegularCycle($RegularCycle);
            $ProductClass->addProductClassRegularCycle($ProductClassRegularCycle);
        }
    }

    public function setRegularDiscount(FormEvent $event)
    {
        /** @var Form $form */
        $form = $event->getForm();
        /** @var ProductClass $ProductClass */
        $ProductClass = $event->getData();
        $RegularDiscountId = $form['RegularDiscount']->getData();
        $RegularDiscount = $this->regularDiscountRepository->findOneBy([
            'discount_id' => $RegularDiscountId,
        ]);
        $ProductClass->setRegularDiscount($RegularDiscount);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ProductClass::class,
        ]);
    }
}
