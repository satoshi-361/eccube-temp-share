<?php
/*
 * Copyright(c) 2020 YAMATO.CO.LTD
 */
namespace Plugin\SitemapXmlGenerator\Controller\Admin\Content;

use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Plugin\SitemapXmlGenerator\Entity\SitemapSetting;
use Plugin\SitemapXmlGenerator\Form\Type\Admin\SitemapSettingType;
use Plugin\SitemapXmlGenerator\Service\SitemapXmlService;

/**
 * sitemap.xml管理画面
 *
 * @author Masaki Okada
 */
class SitemapXmlSettingController extends AbstractController
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
     * 画面 表示アクション
     *
     * @Route("/%eccube_admin_route%/content/sitemapxml/setting", name="admin_content_sitemapxml_setting")
     * @Template("/SitemapXmlGenerator/Resource/template/admin/Content/sitemapxml_setting.twig")
     * @param Request $request
     */
    public function index(Request $request)
    {
        // フォームを取得
        $form = $this->getForm($request);

        return [
            'form' => $form->createView(),
            'SitemapSetting' => $this->SitemapSetting,
        ];
    }

    /**
     * sitemap.xml 出力項目 更新アクション
     *
     * @Route("/%eccube_admin_route%/content/sitemapxml/setting/save", name="admin_content_sitemapxml_setting_save")
     * @param Request $request
     */
    public function save(Request $request)
    {
        // フォームを取得
        $form = $this->getForm($request);

        if (! $form->isSubmitted() || ! $form->isValid()) {
            // 画面表示アクションにリダイレクト
            $this->redirectToRoute('admin_content_sitemapxml_setting');
        }

        // パラメタ取得
        $params = $request->request->get('sitemap_setting');

        // 設定更新
        $this->sitemapXmlService->save($params);

        // 完了メッセージをセット
        $this->addSuccess('admin.common.save_complete', 'admin');

        // 出力項目設定画面 表示アクションにリダイレクト
        return $this->redirectToRoute('admin_content_sitemapxml_setting');
    }

    /**
     * フォームを取得
     *
     * @param Request $request
     */
    protected function getForm(Request $request)
    {
        // sitemap.xmlの設定をDBから取得
        $this->SitemapSetting = $this->sitemapXmlService->getSitemapSetting();

        /* @var FormBuilderInterface */
        $builder = $this->formFactory->createBuilder(SitemapSettingType::class, $this->SitemapSetting);

        $form = $builder->getForm();

        $form->handleRequest($request);

        return $form;
    }
}