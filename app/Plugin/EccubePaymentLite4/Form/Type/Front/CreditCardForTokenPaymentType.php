<?php

namespace Plugin\EccubePaymentLite4\Form\Type\Front;

use DateTime;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Service\GetYearsService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints as Assert;

class CreditCardForTokenPaymentType extends AbstractType
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var GetYearsService
     */
    private $getYearsService;

    public function __construct(ConfigRepository $configRepository, GetYearsService $getYearsService)
    {
        $this->configRepository = $configRepository;
        $this->getYearsService = $getYearsService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);

        $builder
            ->add('contract_code', HiddenType::class, [
                'data' => $Config->getContractCode(),
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'max' => 8,
                        'min' => 8,
                    ]),
                ],
            ])
            ->add('credit_card_number', TextType::class,)
            ->add('holder_name', TextType::class)
            ->add('expiration_month', ChoiceType::class, [
                'choices' => [
                    '-' => null,
                    '1月' => '1',
                    '2月' => '2',
                    '3月' => '3',
                    '4月' => '4',
                    '5月' => '5',
                    '6月' => '6',
                    '7月' => '7',
                    '8月' => '8',
                    '9月' => '9',
                    '10月' => '10',
                    '11月' => '11',
                    '12月' => '12',
                ],
            ])
            ->add('expiration_year', ChoiceType::class, [
                'choices' => $this->getYearsService->get(10),
            ])
            ->add('security_code', TextType::class)
            ->add('token', HiddenType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => '購入処理中にエラーが発生しました。',
                    ]),
                ],
            ])
        ;
        $builder
            ->addEventListener(
                FormEvents::POST_SUBMIT,
                [$this, 'validateExpiration']
            );
    }

    public function validateExpiration(FormEvent $event)
    {
        /** @var Form $form */
        /*
        $form = $event->getForm();
        $form['expiration_month'];
        $form['expiration_year'];
        $inputExpirationDateTime = new DateTime($form['expiration_year']->getData().'-'.sprintf('%02d', $form['expiration_month']->getData()).'-01');
        $firstDayOfThisMonth = new Datetime('first day of this month');
        $firstDayOfThisMonth->setTime(00, 00, 00);

        if ($firstDayOfThisMonth > $inputExpirationDateTime) {
            $form['expiration_year']->addError(new FormError('有効な年月を入力する必要があります。'));
            $form['expiration_month']->addError(new FormError(''));
        }*/
    }
}
