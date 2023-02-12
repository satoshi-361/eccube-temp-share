<?php

namespace Plugin\EccubePaymentLite4\Form\Extension\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\ProductClass;
use Eccube\Form\Type\Admin\ProductClassType;
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

class ProductClassTypeExtension extends AbstractTypeExtension
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
        return ProductClassType::class;
    }

    /**
     * Return the class of the type being extended.
     */
    public static function getExtendedTypes(): iterable
    {
        return [ProductClassType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('RegularDiscount', ChoiceType::class, [
            'choices' => array_flip($this->regularDiscountRepository->getRegularDiscountsChoices()),
            'required' => false,
            'placeholder' => '選択してください',
            'mapped' => false,
        ]);

        $builder
            ->addEventListener(
                FormEvents::POST_SET_DATA,
                [$this, 'setRegularCycleData']
            )
            ->addEventListener(
                FormEvents::POST_SET_DATA,
                [$this, 'setRegularDiscountData']
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
            );
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
        $form['RegularDiscount']->setData($ProductClass->getRegularDiscount());
    }

    public function setRegularCycleData(FormEvent $event)
    {
        /** @var Form $form */
        $form = $event->getForm();
        /** @var ProductClass $ProductClass */
        $ProductClass = $event->getData();
        if (is_null($ProductClass)) {
            return;
        }
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
            ]);
        $form['RegularCycle']->setData($ProductClass->getRegularCycle());
    }

    public function validateProductClassRegularCycle(FormEvent $event)
    {
        /** @var Form $form */
        $form = $event->getForm();
        if ($form['sale_type']->getData()->getName() === '定期商品' && empty($form['RegularCycle']->getData())) {
            $form['RegularCycle']->addError(new FormError('定期サイクルは必須入力です。'));

            return;
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
        /** @var Form $form */
        $form = $event->getForm();
        /** @var ProductClass $ProductClass */
        $ProductClass = $event->getData();
        foreach ($ProductClass->getProductClassRegularCycle() as $ProductClassRegularCycle) {
            $ProductClass->removeProductClassRegularCycle($ProductClassRegularCycle);
            $this->entityManager->remove($ProductClassRegularCycle);
        }
        $RegularCycles = $form['RegularCycle']->getData();
        foreach ($RegularCycles as $RegularCycle) {
            /** @var RegularCycle $RegularCycle */
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
}
