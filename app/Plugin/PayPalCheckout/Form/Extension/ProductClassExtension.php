<?php

namespace Plugin\PayPalCheckout\Form\Extension;

use Eccube\Form\Type\Admin\ProductClassEditType;
use Plugin\PayPalCheckout\Repository\ConfigRepository;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ProductClassExtension
 * @package Plugin\PayPalCheckout\Form\Extension
 */
class ProductClassExtension extends AbstractTypeExtension
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * ProductClassExtension constructor.
     * @param ConfigRepository $configRepository
     */
    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //
    }

    /**
     * {@inheritdoc}
     *
     * EC-CUBE4.0.x(Symfony3.4)との互換性用
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
}
