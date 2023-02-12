<?php

namespace Plugin\EccubePaymentLite4\Form\Extension\Front\Shopping;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Entity\Shipping;
use Eccube\Form\Type\Shopping\OrderType;
use Eccube\Repository\PaymentRepository;
use Plugin\EccubePaymentLite4\Entity\ConvenienceStore;
use Plugin\EccubePaymentLite4\Entity\RegularCycle;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Repository\RegularCycleRepository;
use Plugin\EccubePaymentLite4\Repository\ConvenienceStoreRepository;
use Plugin\EccubePaymentLite4\Service\GetProductClassesRegularCycles;
use Plugin\EccubePaymentLite4\Service\GetRegularCyclesFromProductClassId;
use Plugin\EccubePaymentLite4\Service\IsRegularPaymentService;
use Plugin\EccubePaymentLite4\Service\Method\Conveni;
use Plugin\EccubePaymentLite4\Service\SaveRegularOrderService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrderTypeExtension extends AbstractTypeExtension
{
    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;
    protected $eccubeConfig;
    protected $gmoConfigRepository;

    /**
     * @var RegularCycleRepository
     */
    private $regularCycleRepository;
    /**
     * @var IsRegularPaymentService
     */
    private $isRegularPaymentService;
    /**
     * @var SaveRegularOrderService
     */
    private $saveRegularOrderService;
    /**
     * @var GetRegularCyclesFromProductClassId
     */
    private $getRegularCyclesFromProductClassId;
    /**
     * @var GetProductClassesRegularCycles
     */
    private $getProductClassesRegularCycles;
    /**
     * @var ConvenienceStoreRepository
     */
    private $convenienceStoreRepository;

    public function __construct(
        EccubeConfig $eccubeConfig,
        PaymentRepository $paymentRepository,
        ConfigRepository $gmoConfigRepository,
        GetRegularCyclesFromProductClassId $getRegularCyclesFromProductClassId,
        RegularCycleRepository $regularCycleRepository,
        IsRegularPaymentService $isRegularPaymentService,
        SaveRegularOrderService $saveRegularOrderService,
        GetProductClassesRegularCycles $getProductClassesRegularCycles,
        ConvenienceStoreRepository $convenienceStoreRepository
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->eccubeConfig = $eccubeConfig;
        $this->gmoConfigRepository = $gmoConfigRepository;
        $this->getRegularCyclesFromProductClassId = $getRegularCyclesFromProductClassId;
        $this->regularCycleRepository = $regularCycleRepository;
        $this->isRegularPaymentService = $isRegularPaymentService;
        $this->saveRegularOrderService = $saveRegularOrderService;
        $this->getProductClassesRegularCycles = $getProductClassesRegularCycles;
        $this->convenienceStoreRepository = $convenienceStoreRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
                /** @var Order $Order */
                $Order = $event->getData();
                $form = $event->getForm();

                if (!is_null($Order->getPayment())) {
                    if (Conveni::class !== $Order->getPayment()->getMethodClass()) {
                        return;
                    }
                    // Get conveniences from config
                    $Config = $this->gmoConfigRepository->find(1);
                    $arrConvenience = [];
                    $useConveniences = $Config->getConvenienceStores();
                    foreach ($useConveniences as $store) {
                        $arrConvenience[] = $store['id'];
                    }

                    // Get list convenience store  add to form builder
                    $convenienceStore = $this->convenienceStoreRepository->findBy(['id' => $arrConvenience], [
                        'sort_no' => 'ASC',
                    ]);

                    $message = trans('gmo_epsilon.admin.config.required');

                    if(empty($convenienceStore)){
                        $message = trans("gmo_epsilon.front.shopping.use_conveni_not_fount");
                    }

                    $form->add('convenience', EntityType::class, [
                    'class' => ConvenienceStore::class,
                    'invalid_message' => $message,
                    'expanded' => true,
                    'multiple' => false,
                    'choices' => $convenienceStore,
                    'constraints' => [
                        new NotBlank(['message' => $message]),
                    ],
                    'mapped' => false,
                ]);
                }
            })
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $form = $event->getForm();
                /** @var Order $Order */
                $Order = $event->getData();
                $regularCycleChoices = $this
                    ->getProductClassesRegularCycles
                    ->handle($Order);
                $form
                    ->add('RegularCycles', ChoiceType::class, [
                        'choices' => $regularCycleChoices,
                        'choice_value' => 'id',
                        'choice_label' => function (RegularCycle $regularCycle) {
                            return $regularCycle;
                        },
                        'expanded' => true,
                        'multiple' => false,
                        'mapped' => false,
                    ])
                ;
            })
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $options = $event->getForm()->getConfig()->getOptions();
            if ($options['skip_add_form']) {
                return;
            }
            $Payment = $this->paymentRepository->findOneBy(['method_class' => Conveni::class]);
            $data = $event->getData();
            if (is_null($Payment)) {
                return;
            }
            if ($Payment->getId() == $data['Payment']) {
                return;
            }
            /** @var Form $form */
            $form = $event->getForm();
            $form->remove('convenience');
        });
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var Order $Order */
            $Order = $event->getData();
            if (!$this->isRegularPaymentService->isRegularPayment($Order)) {
                return;
            }
            /** @var Shipping $Shipping */
            $Shipping = $Order->getShippings()->first();
            if ($Shipping->getDelivery()->getSaleType()->getName() !== '定期商品') {
                return;
            }
            if (is_null($event->getForm()['RegularCycles']->getData())) {
                return;
            }
            $this
                ->saveRegularOrderService
                ->setRegularCycleIdInSession(
                    $event->getForm()['RegularCycles']->getData()->getId()
                );
        });
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
