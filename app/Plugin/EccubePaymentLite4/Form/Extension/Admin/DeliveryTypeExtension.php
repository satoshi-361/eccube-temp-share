<?php

namespace Plugin\EccubePaymentLite4\Form\Extension\Admin;

use Doctrine\ORM\EntityRepository;
use Eccube\Entity\Delivery;
use Eccube\Entity\Master\SaleType;
use Eccube\Entity\Payment;
use Eccube\Form\Type\Admin\DeliveryType;
use Eccube\Form\Type\Master\PaymentType;
use Eccube\Repository\Master\SaleTypeRepository;
use Eccube\Service\Payment\Method\Cash;
use Plugin\EccubePaymentLite4\Entity\DeliveryCompany;
use Plugin\EccubePaymentLite4\Service\Method\Credit;
use Plugin\EccubePaymentLite4\Service\Method\Reg_Credit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

class DeliveryTypeExtension extends AbstractTypeExtension
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
    public function getExtendedType()
    {
        return DeliveryType::class;
    }

    /**
     * Return the class of the type being extended.
     */
    public static function getExtendedTypes(): iterable
    {
        return [DeliveryType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Delivery $Delivery */
        $Delivery = $builder->getData();
        $builder
            ->add('DeliveryCompany', EntityType::class, [
                'class' => DeliveryCompany::class,
                'choice_label' => 'name',
                'expanded' => true,
                'multiple' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('payments', PaymentType::class, [
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'mapped' => false,
                'query_builder' => function (EntityRepository $er) use ($Delivery) {
                    $qb = $er->createQueryBuilder('p');
                    // 定期商品の配送設定の場合、支払方法をクレジットのみにする。
                    $saleTypeId = $Delivery->getSaleType()->getId();
                    /** @var SaleType $SaleType */
                    $SaleType = $this->saleTypeRepository->find($saleTypeId);
                    if ($SaleType->getName() === '定期商品') {
                        $qb
                            ->where($qb->expr()->in('p.method_class', ':methodClass'))
                            ->setParameter(':methodClass', [
                                'Plugin\\EccubePaymentLite4\\Service\\Method\\Credit',
                                'Plugin\\EccubePaymentLite4\\Service\\Method\\Reg_Credit',
                                'Eccube\\Service\\Payment\Method\\Cash',
                            ])
                        ;
                    }

                    return $qb;
                },
                'constraints' => [
                    new NotBlank(),
                ],
            ])
        ;
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'validatePayments']);
    }

    public function validatePayments(FormEvent $event)
    {
        /** @var Delivery $Delivery */
        $Delivery = $event->getData();
        if ($Delivery->getSaleType()->getName() !== '定期商品') {
            return;
        }

        /** @var Form $form */
        $form = $event->getForm();
        /** @var Payment[] $Payments */
        $Payments = $form['payments']->getData();
        foreach ($Payments as $Payment) {
            if ($Payment->getMethodClass() !== Credit::class &&
                $Payment->getMethodClass() !== Reg_Credit::class &&
                $Payment->getMethodClass() !== Cash::class) {
                $form['payments']->addError(new FormError($Payment->getMethod().'は定期機能未対応の決済です。'));
            }
        }
    }
}
