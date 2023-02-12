<?php

namespace Plugin\EccubePaymentLite4\Form\Type\Admin;

use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\RegularCycle;
use Plugin\EccubePaymentLite4\Entity\RegularCycleType;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegularCycleFormType extends AbstractType
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('regular_cycle_type', EntityType::class, [
                'class' => RegularCycleType::class,
                'required' => false,
                'constraints' => [
                    new NotBlank([
                        'message' => '定期サイクルの種類を入力してください。',
                    ]),
                ],
            ])
            ->add('day', ChoiceType::class, [
                'required' => false,
                'choices' => $this->getDays(),
                'multiple' => false,
                'expanded' => false,
            ])
            ->add('week', ChoiceType::class, [
                'required' => false,
                'choices' => $this->getWeeks(),
                'multiple' => false,
                'expanded' => false,
            ])
        ;
        $builder
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'validateFormDataDay'])
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'validateFormDataSpecificWeek']);
    }

    public function validateFormDataDay(FormEvent $event)
    {
        $form = $event->getForm();
        $regularCycleId = $form['regular_cycle_type']->getData()->getId();
        if (!(
            $regularCycleId === RegularCycleType::REGULAR_DAILY_CYCLE ||
            $regularCycleId === RegularCycleType::REGULAR_WEEKLY_CYCLE ||
            $regularCycleId === RegularCycleType::REGULAR_MONTHLY_CYCLE ||
            $regularCycleId === RegularCycleType::REGULAR_SPECIFIC_DAY_CYCLE
        )) {
            return;
        }
        $day = $form['day']->getData();
        if (is_null($day)) {
            $form['day']->addError(new FormError('日付は必須入力です。'));

            return;
        }
        if ($regularCycleId === RegularCycleType::REGULAR_DAILY_CYCLE) {
            if ($day <= 0 || 100 <= $day) {
                $form['day']->addError(new FormError('日付の値が不正です。'));

                return;
            }
        }
        if ($regularCycleId === RegularCycleType::REGULAR_DAILY_CYCLE) {
            /** @var Config $Config */
            $Config = $this->configRepository->find(1);
            $deadLine = $Config->getRegularOrderDeadline();
            if ($day <= $deadLine) {
                $form['day']->addError(new FormError('「定期受注注文締切日」よりも大きな値を設定する必要があります。'));

                return;
            }
        }
        if ($regularCycleId === RegularCycleType::REGULAR_WEEKLY_CYCLE) {
            if ($day <= 0 || 5 <= $day) {
                $form['day']->addError(new FormError('日付の値が不正です。'));

                return;
            }
        }
        if ($regularCycleId === RegularCycleType::REGULAR_MONTHLY_CYCLE) {
            if ($day <= 0 || 13 <= $day) {
                $form['day']->addError(new FormError('日付の値が不正です。'));

                return;
            }
        }
        if ($regularCycleId === RegularCycleType::REGULAR_SPECIFIC_DAY_CYCLE) {
            if ($day <= 0 || 32 <= $day) {
                $form['day']->addError(new FormError('日付の値が不正です。'));

                return;
            }
        }
    }

    public function validateFormDataSpecificWeek(FormEvent $event)
    {
        $form = $event->getForm();
        $regularCycleId = $form['regular_cycle_type']->getData()->getId();
        if ($regularCycleId !== RegularCycleType::REGULAR_SPECIFIC_WEEK_CYCLE) {
            return;
        }
        $week = $form['week']->getData();

        if (is_null($week)) {
            $form['week']->addError(new FormError('曜日は必須入力です。'));

            return;
        }
        if ($week < 1 || 8 <= $week) {
            $form['week']->addError(new FormError('曜日の値が不正です。'));

            return;
        }
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        $deadLine = $Config->getRegularOrderDeadline();
        if (7 <= $deadLine) {
            $form['week']->addError(new FormError('定期受注注文締切日よりも定期サイクルが大きくなってしまうため、「特定の曜日」は設定できません。'));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RegularCycle::class,
        ]);
    }

    private function getDays(): array
    {
        $days = ['-' => null];

        return array_merge($days, array_combine(range(1, 99), range(1, 99)));
    }

    private function getWeeks(): array
    {
        return [
            '-' => null,
            '日曜日' => RegularCycle::SUNDAY,
            '月曜日' => RegularCycle::MONDAY,
            '火曜日' => RegularCycle::TUESDAY,
            '水曜日' => RegularCycle::WEDNESDAY,
            '木曜日' => RegularCycle::THURSDAY,
            '金曜日' => RegularCycle::FRIDAY,
            '土曜日' => RegularCycle::SATURDAY,
        ];
    }
}
