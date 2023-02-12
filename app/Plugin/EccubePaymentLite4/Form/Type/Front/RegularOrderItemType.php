<?php

namespace Plugin\EccubePaymentLite4\Form\Type\Front;

use Eccube\Common\EccubeConfig;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\EccubePaymentLite4\Entity\RegularOrderItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegularOrderItemType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    public function __construct(
        EccubeConfig $eccubeConfig
    ) {
        $this->eccubeConfig = $eccubeConfig;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('quantity', IntegerType::class, [
                'constraints' => [
                    new NotBlank(),
                    new GreaterThanOrEqual([
                        'value' => 1,
                    ]),
                    new Regex(['pattern' => '/^\d+$/']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RegularOrderItem::class,
        ]);
    }
}
