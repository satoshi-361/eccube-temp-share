<?php

namespace Plugin\EccubePaymentLite4\Form\Extension\Admin;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Form\Type\Admin\OrderType;
use Plugin\EccubePaymentLite4\Entity\PaymentStatus;
use Plugin\EccubePaymentLite4\Repository\PaymentStatusRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class OrderTypeExtension extends AbstractTypeExtension
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;
    /**
     * @var PaymentStatusRepository
     */
    private $paymentStatusRepository;

    public function __construct(
        EccubeConfig $eccubeConfig,
        PaymentStatusRepository $paymentStatusRepository
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->paymentStatusRepository = $paymentStatusRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Order $Order */
        $Order = $builder->getForm()->getData();
        if (is_null($Order) || !$Order->getId()) {
            return;
        }
        $statuses = [
            PaymentStatus::UNPAID,
            PaymentStatus::CHARGED,
            PaymentStatus::TEMPORARY_SALES,
            PaymentStatus::CANCEL,
        ];
        if ($Order->getPaymentMethod() === $this->eccubeConfig['gmo_epsilon']['pay_name']['deferred']) {
            $statuses[] = PaymentStatus::UNDER_REVIEW;
            $statuses[] = PaymentStatus::SHIPPING_REGISTRATION;
            $statuses[] = PaymentStatus::EXAMINATION_NG;
        }
        $paymentStatuses = $this->paymentStatusRepository->findBy(['id' => $statuses], [
            'sort_no' => 'ASC',
        ]);
        $builder
            ->add('PaymentStatus', EntityType::class, [
                'required' => false,
                'class' => PaymentStatus::class,
                'choices' => $paymentStatuses,
                'placeholder' => '-',
                'mapped' => false,
                'data' => $Order->getPaymentStatus(),
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return OrderType::class;
    }

    /**
     * Return the class of the type being extended.
     */
    public static function getExtendedTypes(): iterable
    {
        return [OrderType::class];
    }
}
