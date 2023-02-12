<?php

namespace Plugin\EccubePaymentLite4\Form\Type\Front;

use Plugin\EccubePaymentLite4\Entity\RegularCycle;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Service\GetRegularCyclesFromProductClassId;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegularCycleType extends AbstractType
{
    /**
     * @var GetRegularCyclesFromProductClassId
     */
    private $getRegularCyclesFromProductClassId;

    public function __construct(GetRegularCyclesFromProductClassId $getRegularCyclesFromProductClassId)
    {
        $this->getRegularCyclesFromProductClassId = $getRegularCyclesFromProductClassId;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $form = $event->getForm();
                /** @var RegularOrder $RegularOrder */
                $RegularOrder = $event->getData();
                $productClassId = $RegularOrder->getRegularProductOrderItems()[0]->getProductClass()->getId();
                $form
                    ->add('RegularCycle', ChoiceType::class, [
                        'choices' => $this->getRegularCyclesFromProductClassId->handle($productClassId),
                        'choice_value' => 'id',
                        'choice_label' => function (RegularCycle $regularCycle) {
                            return $regularCycle;
                        },
                        'expanded' => true,
                        'multiple' => false,
                    ])
                ;
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RegularOrder::class,
        ]);
    }
}
