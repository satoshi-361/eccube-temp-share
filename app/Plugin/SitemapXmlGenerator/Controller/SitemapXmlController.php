<?php
/*
 * Copyright(c) 2020 YAMATO.CO.LTD
 */
namespace Plugin\SitemapXmlGenerator\Controller;

use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Plugin\SitemapXmlGenerator\Entity\SitemapSetting;
use Plugin\SitemapXmlGenerator\Form\Type\Admin\SitemapSettingType;
use Plugin\SitemapXmlGenerator\Service\SitemapXmlService;

/**
 * sitemap.xml画面
 *
 * @author Masaki Okada
 */
class SitemapXmlController extends AbstractController
{

    /**
     *
     * @var SitemapXmlService
     */
    protected $sitemapXmlService;

    /**
     *
     * @var SitemapSetting
     */
    protected $SitemapSetting;

    /**
     * コンストラクタ
     *
     * @param SitemapXmlService $sitemapXmlService
     */
    public function __construct(SitemapXmlService $sitemapXmlService)
    {
        $this->sitemapXmlService = $sitemapXmlService;
    }

    /**
     * sitemap.xml 表示アクション
     *
     * @Route("/sitemap.xml", name="sitemap_xml", defaults={"_format"="xml"})
     * @Template("/SitemapXmlGenerator/Resource/template/sitemapxml.twig")
     */
    public function sitemapxml(Request $request)
    {
        return $this->sitemapXmlService->getSitemapXmlData();
    }
}