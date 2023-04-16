<?php
namespace Plugin\JsysAsi\Form\Extension\Admin;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Eccube\Form\Type\Admin\LoginType;
use Plugin\JsysAsi\Repository\ConfigRepository;
use Plugin\JsysAsi\Entity\Config;

/**
 * 管理ログイン拡張FormType
 * @author manabe
 *
 */
class LoginTypeExtension extends AbstractTypeExtension
{
    /**
     * @var Config
     */
    private $Config;


    /**
     * LoginTypeExtension constructor.
     * @param ConfigRepository $configRepo
     */
    public function __construct(ConfigRepository $configRepo)
    {
        $this->Config = $configRepo->get();
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Form\AbstractTypeExtension::buildForm()
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->Config->getOptionTfa()) {
            $builder
                ->add('jsys_asi_otp', TextType::class, [
                    'required' => false,
                    'mapped'   => false,
                    'attr'     => [
                        'placeholder' => 'jsys_asi.admin.login.otp',
                    ],
                ]);
        }
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Form\FormTypeExtensionInterface::getExtendedType()
     */
    public function getExtendedType()
    {
        return LoginType::class;
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Form\FormTypeExtensionInterface::getExtendedTypes()
     */
    public static function getExtendedTypes(): iterable
    {
        return [LoginType::class];
    }
}
