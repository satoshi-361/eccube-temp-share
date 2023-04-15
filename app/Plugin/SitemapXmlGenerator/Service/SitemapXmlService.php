<?php
/*
 * Copyright(c) 2020 YAMATO.CO.LTD
 */
namespace Plugin\SitemapXmlGenerator\Service;

use Plugin\SitemapXmlGenerator\Repository\SitemapXmlRepository;
use Plugin\SitemapXmlGenerator\Entity\SitemapSetting;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\PageRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Entity\Page;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\Category;
use Eccube\Entity\Product;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Plugin\SitemapXmlGenerator\ValueObject\SitemapUrl;
use Eccube\Util\StringUtil;

/**
 * SitemapXmlService
 *
 * @author Masaki Okada
 */
class SitemapXmlService
{

    /**
     *
     * @var SitemapXmlRepository
     */
    protected $sitemapXmlRepository;

    /**
     *
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     *
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     *
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     *
     * @var UrlGeneratorInterface
     */
    protected $router;

    /**
     * ベースディレクトリまでのサイトのURL
     *
     * @var string
     */
    protected $siteUrl;

    /**
     * 除外ディレクトリ
     *
     * @var array
     */
    protected $ignoreDirectories;

    /**
     * SitemapUrlの配列
     *
     * @var array
     */
    protected $SitemapUrls;

    /**
     * コンストラクタ
     *
     * @param SitemapXmlRepository $sitemapXmlRepository
     * @param PageRepository $pageRepository
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     * @param UrlGeneratorInterface $router
     */
    public function __construct(SitemapXmlRepository $sitemapXmlRepository, PageRepository $pageRepository,
        CategoryRepository $categoryRepository, ProductRepository $productRepository, UrlGeneratorInterface $router)
    {
        $this->sitemapXmlRepository = $sitemapXmlRepository;
        $this->pageRepository = $pageRepository;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->router = $router;
    }

    /**
     * SitemapSettingエンティティを返却する
     *
     * @return SitemapSetting
     */
    public function getSitemapSetting()
    {
        // 設定を取得
        $SitemapSetting = $this->sitemapXmlRepository->findBy([], null, 1);

        // 存在する場合はエンティティを返却
        if (! is_null($SitemapSetting) && ! empty($SitemapSetting)) {
            return $SitemapSetting[0];
        }

        // 存在しない場合は、デフォルトの設定値を返却
        return $this->getDefaultSitemapSetting();
    }

    /**
     * 初期値を返却
     *
     * @return SitemapSetting
     */
    public function getDefaultSitemapSetting()
    {
        $SitemapSetting = new SitemapSetting();
        $SitemapSetting->setLastmodAvailed(false);
        $SitemapSetting->setChangefreq(0);
        $SitemapSetting->setPriorityAvailed(false);
        $SitemapSetting->setExcludedDirectories('complete,guide,nonmember');
        $SitemapSetting->setDiscriminatorType('sitemap_setting');

        return $SitemapSetting;
    }

    /**
     * 登録処理
     *
     * @param array $params
     * @return void
     */
    public function save($params)
    {
        $SitemapSetting = $this->sitemapXmlRepository->findBy([], null, 1);

        if (is_null($SitemapSetting) || empty($SitemapSetting)) {
            $SitemapSetting = new SitemapSetting();
        } else {
            $SitemapSetting = $SitemapSetting[0];
        }

        $SitemapSetting->setLastmodAvailed($params['lastmodAvailed']);
        $SitemapSetting->setChangefreq($params['changefreq']);
        $SitemapSetting->setPriorityAvailed($params['priorityAvailed']);
        $SitemapSetting->setExcludedDirectories($params['excludedDirectories']);
        $SitemapSetting->setDiscriminatorType('sitemap_setting');
        $this->sitemapXmlRepository->save($SitemapSetting);
        $this->sitemapXmlRepository->flush($SitemapSetting);
    }

    /**
     * sitemap.xml用のデータを組み立てる。
     *
     * @return void
     */
    public function getSitemapXmlData()
    {
        // DBから設定を取得する
        $SitemapSetting = $this->getSitemapSetting();

        // ページを取得
        $where = '(p.meta_robots <> :meta_robots OR p.meta_robots IS NULL)';
        $Pages = $this->pageRepository->getPageList($where, [
            'meta_robots' => 'noindex'
        ]);

        // カテゴリを取得
        $Categories = $this->categoryRepository->getList(null, true);

        // 商品を取得
        $Products = $this->productRepository->findBy([
            'Status' => ProductStatus::DISPLAY_SHOW
        ], [
            'update_date' => 'DESC'
        ]);

        // 戻り値を組み立てる
        $result = $this->buildSitemapXmlData($SitemapSetting, $Pages, $Categories, $Products);

        return $result;
    }

    /**
     * 戻り値組み立て
     *
     * @param SitemapSetting $SitemapSetting
     * @param array $Pages
     * @param array $Categories
     * @param array $Products
     * @return array
     */
    protected function buildSitemapXmlData(SitemapSetting $SitemapSetting, array $Pages, array $Categories,
        array $Products)
    {
        // 戻り値初期化
        $this->SitemapUrls = [];

        // 戻り値用の配列にページのURLをセット
        // サイトURLを取得
        $siteUrl = $this->getSiteUrl();

        // 除外ディレクトリリスト
        $ignoreDirectories = $this->getIgnoreDirectories($SitemapSetting);

        // 各ページのURLをセットする
        /** @var Page $Page */
        foreach ($Pages as $Page) {

            $SitemapUrl = new SitemapUrl();

            $pageUrl = $Page->getUrl();

            // 本会員登録(完了ページ)は、パラメタにsecret_keyが必要な為、除外する。
            if (strcmp('entry_activate', $pageUrl) === 0) {
                continue;
            }

            // 商品一覧の場合
            if (strcmp('product_list', $pageUrl) === 0) {
                // スラッグ
                $slug = $this->router->generate('product_list', [
                    'category_id' => 0
                ]);

                if (! $this->isExistIgnoreDirectory($slug, $ignoreDirectories)) {
                    // 商品一覧のURLをセットする
                    $this->setSitemapUrlForCategories($SitemapSetting, $Categories);
                }
                continue;
            }

            // 商品詳細の場合
            if (strcmp('product_detail', $pageUrl) === 0) {
                // スラッグ
                $slug = $this->router->generate('product_detail', [
                    'id' => 0
                ]);

                if (! $this->isExistIgnoreDirectory($slug, $ignoreDirectories)) {
                    // 商品詳細のURLをセットする
                    $this->setSitemapUrlForProducts($SitemapSetting, $Products);
                }
                continue;
            }

            try {
                // パラメタが不要のスラッグ取得
                $slug = $this->router->generate($pageUrl);

                if ($this->isExistIgnoreDirectory($slug, $ignoreDirectories)) {
                    // スラッグに除外するディレクトリが含まれている場合は処理しない
                    continue;
                }
            } catch (\Symfony\Component\Routing\Exception\RouteNotFoundException $ex) {
            } catch (\Symfony\Component\Routing\Exception\MissingMandatoryParametersException $ex) {
            }

            // 全ての項目をセット
            $SitemapUrl->setSitemapUrl($SitemapSetting, $siteUrl . $slug, $Page->getUpdateDate(), $pageUrl);

            array_push($this->SitemapUrls, $SitemapUrl);
        }

        return [
            'SitemapSetting' => $SitemapSetting,
            'SitemapUrls' => $this->SitemapUrls
        ];
    }

    /**
     * 商品一覧のURLをセット
     *
     * @param SitemapSetting $SitemapSetting
     * @param array $Categories
     */
    protected function setSitemapUrlForCategories(SitemapSetting $SitemapSetting, array $Categories)
    {
        // サイトURLを取得
        $siteUrl = $this->getSiteUrl();

        $pageUrl = 'product_list';

        /** @var Category $Category */
        foreach ($Categories as $Category) {
            $SitemapUrl = new SitemapUrl();

            $slug = $this->router->generate($pageUrl, [
                'category_id' => $Category->getId()
            ]);

            // 全ての項目をセット
            $SitemapUrl->setSitemapUrl($SitemapSetting, $siteUrl . $slug, $Category->getUpdateDate(), $pageUrl);

            array_push($this->SitemapUrls, $SitemapUrl);
        }
    }

    /**
     * 商品詳細のURLをセット
     *
     * @param SitemapSetting $SitemapSetting
     * @param array $Products
     */
    protected function setSitemapUrlForProducts(SitemapSetting $SitemapSetting, array $Products)
    {
        // サイトURLを取得
        $siteUrl = $this->getSiteUrl();

        $pageUrl = 'product_detail';

        /** @var Product $Product */
        foreach ($Products as $Product) {
            $SitemapUrl = new SitemapUrl();

            $slug = $this->router->generate($pageUrl, [
                'id' => $Product->getId()
            ]);

            // 全ての項目をセット
            $SitemapUrl->setSitemapUrl($SitemapSetting, $siteUrl . $slug, $Product->getUpdateDate(), $pageUrl);

            array_push($this->SitemapUrls, $SitemapUrl);
        }
    }

    /**
     * スラッグに無視するディレクトリ含まれているか調べる
     *
     * @param string $slug
     * @param array $ignoreDirectories
     * @return boolean
     */
    protected function isExistIgnoreDirectory($slug, array $ignoreDirectories)
    {
        $isExist = false;
        foreach ($ignoreDirectories as $ignoreDirectory) {
            if (0 < preg_match('/' . trim($ignoreDirectory) . '/', $slug)) {
                $isExist = true;
                break;
            }
        }

        return $isExist;
    }

    /**
     * サイトのURLを組み立てて返却
     *
     * @return string
     */
    protected function getSiteUrl()
    {
        if (! empty($this->siteUrl)) {
            return $this->siteUrl;
        }

        $context = $this->router->getContext();

        $siteUrl = $context->getScheme() . '://';

        $siteUrl .= $context->getHost();

        // ポートベースのバーチャルホストで開発や運用している場合、ポート番号を付ける
        if (strcmp('https', $context->getScheme()) === 0) {
            if (443 != $context->getHttpsPort()) {
                $siteUrl .= ':' . $context->getHttpsPort();
            }
        } else {
            if (80 != $context->getHttpPort()) {
                $siteUrl .= ':' . $context->getHttpPort();
            }
        }

        if (StringUtil::isNotBlank($context->getBaseUrl())) {
            $siteUrl .= $context->getBaseUrl();
        }

        $this->siteUrl = $siteUrl;

        return $this->siteUrl;
    }

    /**
     * 除外ディレクトリ
     *
     * @param SitemapSetting $SitemapSetting
     * @return array
     */
    protected function getIgnoreDirectories(SitemapSetting $SitemapSetting)
    {
        if (is_null($this->ignoreDirectories) && ! is_array($this->ignoreDirectories)) {
            // 除外ディレクトリ
            $ignoreDirectories = $SitemapSetting->getExcludedDirectories();
            if (StringUtil::isNotBlank($ignoreDirectories)) {
                $this->ignoreDirectories = explode(',', $ignoreDirectories);
            } else {
                $this->ignoreDirectories = array();
            }
        }

        return $this->ignoreDirectories;
    }
}
