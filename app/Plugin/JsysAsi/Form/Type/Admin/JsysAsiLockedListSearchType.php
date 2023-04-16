<?php
namespace Plugin\JsysAsi\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Eccube\Common\EccubeConfig;

/**
 * ロック済み一覧検索FormType
 * @author manabe
 *
 */
class JsysAsiLockedListSearchType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;


    /**
     * JsysAsiLockedListSearchType constructor.
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
        $builder->add('ip_address', TextType::class, [
            'label'       => 'jsys_asi.admin.locked_list.search_ip',
            'required'    => false,
            'constraints' => [
                new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
            ],
        ]);
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Form\AbstractType::getBlockPrefix()
     */
    public function getBlockPrefix()
    {
        return 'jsys_asi_admin_locked_list_search';
    }

}
