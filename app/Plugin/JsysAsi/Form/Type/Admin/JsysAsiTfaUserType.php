<?php
namespace Plugin\JsysAsi\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Eccube\Common\EccubeConfig;
use Plugin\JsysAsi\Entity\JsysAsiTfaUser;
use Plugin\JsysAsi\Service\JsysAsiTfaService;

/**
 * 2要素認証ユーザーFormType
 * @author manabe
 *
 */
class JsysAsiTfaUserType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var JsysAsiTfaService
     */
    protected $jsysAsiTfaService;


    /**
     * @param EccubeConfig $eccubeConfig
     * @param JsysAsiTfaService $jsysAsiTfaService
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        JsysAsiTfaService $jsysAsiTfaService
    ) {
        $this->eccubeConfig      = $eccubeConfig;
        $this->jsysAsiTfaService = $jsysAsiTfaService;
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Form\AbstractType::buildForm()
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $config = $this->eccubeConfig;

        $builder
            ->add('otp', TextType::class, [
                'required'    => true,
                'mapped'      => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => $config['jsys_asi_tfa_code_len']]),
                ],
                'attr'        => [
                    'placeholder' => 'jsys_asi.admin.tfa_user.edit.otp.placeholder',
                ],
            ]);

        // 値の検証を追加
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    /**
     * 値の検証を追加します。
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        /** @var JsysAsiTfaUser $data */
        $data = $form->getData();

        // 認証コードチェック
        $otp = $form['otp']->getData();
        if ($otp && !$this->jsysAsiTfaService->checkCode($data, $otp)) {
            $form['otp']->addError(new FormError(trans(
                'jsys_asi.tfa.check_code.failure'
            )));
        }
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Form\AbstractType::configureOptions()
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => JsysAsiTfaUser::class,
        ]);
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Form\AbstractType::getBlockPrefix()
     */
    public function getBlockPrefix()
    {
        return 'jsys_asi_admin_tfa_user';
    }

}
