<?php
namespace Plugin\JsysAsi\Form\Type\Admin;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Eccube\Common\EccubeConfig;
use Plugin\JsysAsi\Entity\JsysAsiLoginHistoryLockStatus;
use Plugin\JsysAsi\Entity\JsysAsiLoginHistoryStatus;
use Plugin\JsysAsi\Entity\JsysAsiLoginHistoryTfaStatus;

/**
 * ログイン履歴検索FormType
 * @author manabe
 *
 */
class JsysAsiLoginHistorySearchType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;


    /**
     * JsysAsiLoginHistorySearchType constructor.
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Form\AbstractType::buildForm()
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $config = $this->eccubeConfig;
        $prefix = $this->getBlockPrefix();

        $builder
            ->add('multi', TextType::class, [
                'label'       => 'jsys_asi.admin.login_history.search_multi',
                'required'    => false,
                'constraints' => [
                    new Assert\Length(['max' => $config['eccube_stext_len']])
                ],
            ])
            ->add('login_date_start', DateTimeType::class, [
                'label'    => 'jsys_asi.admin.login_history.search_login_date_start',
                'required' => false,
                'input'    => 'datetime',
                'widget'   => 'single_text',
                'format'   => 'yyyy-MM-dd HH:mm:ss',
                'attr'     => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#' . $prefix . '_login_date_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('login_date_end', DateTimeType::class, [
                'label'    => 'jsys_asi.admin.login_history.search_login_date_end',
                'required' => false,
                'input'    => 'datetime',
                'widget'   => 'single_text',
                'format'   => 'yyyy-MM-dd HH:mm:ss',
                'attr'     => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#' . $prefix . '_login_date_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('status', EntityType::class, [
                'class'    => JsysAsiLoginHistoryStatus::class,
                'label'    => 'jsys_asi.admin.login_history.search_status',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('tfa_status', EntityType::class, [
                'class'    => JsysAsiLoginHistoryTfaStatus::class,
                'label'    => 'jsys_asi.admin.login_history.search_tfa_status',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('lock_status', EntityType::class, [
                'class'    => JsysAsiLoginHistoryLockStatus::class,
                'label'    => 'jsys_asi.admin.login_history.search_lock_status',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
            ]);
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Form\AbstractType::getBlockPrefix()
     */
    public function getBlockPrefix()
    {
        return 'jsys_asi_admin_login_history_search';
    }

}
