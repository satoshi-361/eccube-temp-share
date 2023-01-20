<?php

namespace Plugin\PayPalCheckout\Form\Type\Admin\Subscription;

use Eccube\Common\EccubeConfig;
use Plugin\PayPalCheckout\Entity\SubscribingCustomer;
use Plugin\PayPalCheckout\Entity\SubscribingCustomerCondition;
use Plugin\PayPalCheckout\Repository\SubscribingCustomerRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class SubscribingCustomerConditionType
 * @package Plugin\PayPalCheckout\Form\Type\Admin
 */
class SubscribingCustomerConditionType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var array
     */
    protected $pricingCourses;

    /**
     * SubscribingCustomerConditionType constructor.
     * @param EccubeConfig $eccubeConfig
     * @param SubscribingCustomerRepository $subscribingCustomerRepository
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        SubscribingCustomerRepository $subscribingCustomerRepository)
    {
        $this->eccubeConfig = $eccubeConfig;

        // プラン検索用の選択肢を取得。現在継続決済がされているもののみ取得する。
        $SubscribingCustomers = $subscribingCustomerRepository->findAll();
        $pricingCourses = [];
        foreach ($SubscribingCustomers as $subscribingCustomer) {
            if (is_null($subscribingCustomer)) continue;
            $key = $subscribingCustomer->getProductClass()->getClassCategory1()->getName();
            $value = $subscribingCustomer->getProductClass()->getId();
            $pricingCourses[$key] = $value;
        }
        $this->pricingCourses = array_merge(['指定なし' => ''], $pricingCourses);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
            'label' => 'admin.order.orderer_name',
            'required' => false,
        ]);

        $builder->add('price_courses', ChoiceType::class, [
            'choices' => $this->pricingCourses,
            'multiple' => false,
            'expanded' => false,
            'required' => false,
        ]);

        $builder->add('is_deleted', CheckboxType::class, [
            'label' => '表示する',
            'value' => '1',
            'required' => false,
        ]);

        $builder->add('is_failed', CheckboxType::class, [
            'label' => '表示する',
            'value' => '1',
            'required' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SubscribingCustomerCondition::class
        ]);
    }
}
