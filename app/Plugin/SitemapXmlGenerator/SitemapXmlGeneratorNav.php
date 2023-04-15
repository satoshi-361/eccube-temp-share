<?php
/*
 * Copyright(c) 2020 YAMATO.CO.LTD
 */
namespace Plugin\SitemapXmlGenerator;

use Eccube\Common\EccubeNav;

/**
 * SitemapXmlGeneratorNav
 *
 * @author masakiokada
 */
class SitemapXmlGeneratorNav implements EccubeNav
{

    /**
     *
     * @return array
     */
    public static function getNav()
    {
        return [
            // 第一階層にメニューを追加
            'content' => [
                'children' => [
                    'sitemapxmlgenerator' => [
                        'name' => 'sitemap.xml管理',
                        'url' => 'admin_content_sitemapxml_setting',
                    ],
                ],
            ],
        ];
    }
}