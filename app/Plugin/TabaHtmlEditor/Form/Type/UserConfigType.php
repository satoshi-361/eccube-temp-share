<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaHtmlEditor\Form\Type;

use Plugin\TabaHtmlEditor\Common\Constants;
use Eccube\Common\EccubeConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Doctrine\ORM\EntityManagerInterface;

class UserConfigType extends AbstractType
{

    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    /**
     * 
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
        EccubeConfig $eccubeConfig
    ) {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('user_config', TextareaType::class, array(
            'label' => trans(Constants::PLUGIN_CODE_LC . '.admin.label.user_config'),
            'attr' => ['rows' => '20'],
            'required' => false,
            'constraints' => array(
                new Assert\Length(array(
                    'max' => (1024 * 100)
                ))
            )
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return Constants::PLUGIN_CODE_LC . '_type';
    }
}
