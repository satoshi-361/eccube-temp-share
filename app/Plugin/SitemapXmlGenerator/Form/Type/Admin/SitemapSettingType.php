<?php
/*
 * Copyright(c) 2020 YAMATO.CO.LTD
 */
namespace Plugin\SitemapXmlGenerator\Form\Type\Admin;

use Plugin\SitemapXmlGenerator\Entity\SitemapSetting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * SitemapSettingType
 *
 * @author masakiokada
 */
class SitemapSettingType extends AbstractType
{

    /**
     * 更新頻度リスト
     *
     * @var array
     */
    const CHANGEFREQ_LIST = [
        'admin.sitemapxmlgenerator.none' => 0,
        'admin.sitemapxmlgenerator.always' => 1,
        'admin.sitemapxmlgenerator.hourly' => 2,
        'admin.sitemapxmlgenerator.daily' => 3,
        'admin.sitemapxmlgenerator.weekly' => 4,
        'admin.sitemapxmlgenerator.monthly' => 5,
        'admin.sitemapxmlgenerator.yearly' => 6,
        'admin.sitemapxmlgenerator.never' => 7
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // オプションボタン
        $choices = array(
            'admin.sitemapxmlgenerator.no' => 0,
            'admin.sitemapxmlgenerator.yes' => 1
        );

        $builder->
        // 最終更新日:lastmodの自動取得 ラジオボタン
        add('lastmodAvailed', ChoiceType::class,
            [
                'choices' => $choices,
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'label' => 'admin.sitemapxmlgenerator.lastmodAvailed',
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->
        // 更新頻度 セレクトボックス
        add('changefreq', ChoiceType::class,
            [
                'choices' => self::CHANGEFREQ_LIST,
                'required' => true,
                'expanded' => false,
                'multiple' => false,
                'label' => 'admin.sitemapxmlgenerator.lastmodAvailed',
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->
        // 優先度:priorityの自動設定 ラジオボタン
        add('priorityAvailed', ChoiceType::class,
            [
                'choices' => $choices,
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'label' => 'admin.sitemapxmlgenerator.priorityAvailed',
                'constraints' => [
                    new Assert\NotBlank()
                ]
            ])
            ->
        // 除外ディレクトリ テキストフィールド
        add('excludedDirectories', TextType::class, [
            'constraints' => [
                new Assert\Length([
                    'max' => 1024
                ])
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SitemapSetting::class
        ]);
    }
}
