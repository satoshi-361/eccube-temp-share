<?php

namespace Plugin\EccubePaymentLite4\Form\Type\Front;

use Doctrine\ORM\EntityRepository;
use Eccube\Entity\DeliveryTime;
use Eccube\Repository\DeliveryTimeRepository;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\RegularShipping;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Service\NextDeliveryChangeableRangeService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RegularNextDeliveryDateType extends AbstractType
{
    /**
     * @var NextDeliveryChangeableRangeService
     */
    private $nextDeliveryChangeableRangeService;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var DeliveryTimeRepository
     */
    private $deliveryTimeRepository;

    public function __construct(
        NextDeliveryChangeableRangeService $nextDeliveryChangeableRangeService,
        ConfigRepository $configRepository,
        DeliveryTimeRepository $deliveryTimeRepository
    ) {
        $this->nextDeliveryChangeableRangeService = $nextDeliveryChangeableRangeService;
        $this->configRepository = $configRepository;
        $this->deliveryTimeRepository = $deliveryTimeRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var RegularShipping $RegularShipping */
        $RegularShipping = $builder->getData();
        $changeableDays = $this->nextDeliveryChangeableRangeService->get(clone $RegularShipping);

        $Delivery = $RegularShipping->getDelivery();
        $timeId = $RegularShipping->getTimeId();
        $DeliveryTime = null;
        if ($timeId) {
            $DeliveryTime = $this->deliveryTimeRepository->find($timeId);
        }
        $builder
            ->add('NextDeliveryDate', ChoiceType::class, [
                'choices' => $changeableDays,
                'data' => $changeableDays[array_search($RegularShipping->getNextDeliveryDate(), $changeableDays)],
                'constraints' => [
                    new Callback(function (
                        $object,
                        ExecutionContextInterface $context
                    ) {
                        /** @var Config $Config */
                        $Config = $this->configRepository->find(1);
                        $deadlineDate = new \DateTime('today');
                        $deadlineDate->modify('+'.$Config->getRegularOrderDeadline().' day');
                        if (is_null($deadlineDate)) {
                            return;
                        }
                        if ($deadlineDate >= $object) {
                            $context
                                ->buildViolation('「次回お届け予定日」は「定期受注注文締切日」より後の日付を設定する必要があります。')
                                ->atPath('next_delivery_date')
                                ->addViolation();
                        }
                    }),
                ],
            ])
            ->add('ShippingDeliveryTime', EntityType::class, [
                'class' => DeliveryTime::class,
                'placeholder' => 'common.select__unspecified',
                'required' => false,
                'query_builder' => function (EntityRepository $er) use ($Delivery) {
                    return $er
                        ->createQueryBuilder('dt')
                        ->orderBy('dt.visible', 'DESC')
                        ->addOrderBy('dt.sort_no', 'ASC')
                        ->where('dt.Delivery = :Delivery')
                        ->setParameter('Delivery', $Delivery);
                },
                'data' => $DeliveryTime,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RegularShipping::class,
        ]);
    }
}
