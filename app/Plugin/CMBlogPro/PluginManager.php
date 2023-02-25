<?php

namespace Plugin\CMBlogPro;

use Doctrine\ORM\EntityManagerInterface;

use Eccube\Common\Constant;
use Eccube\Plugin\AbstractPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
// Entity
use Eccube\Entity\Block;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Entity\Master\DeviceType;
use Eccube\Entity\Product;

use Plugin\CMBlogPro\Entity\BlogStatus;
use Plugin\CMBlogPro\Entity\Config;
use Plugin\CMBlogPro\Entity\BlogCategory;
use Plugin\CMBlogPro\Entity\Blog;
use Plugin\CMBlogPro\Entity\BlogImage;
use Plugin\CMBlogPro\Entity\BlogProduct;
use Plugin\CMBlogPro\Entity\Category;
// Repository
use Eccube\Repository\BlockRepository;
use Eccube\Repository\LayoutRepository;
use Eccube\Repository\PageRepository;
use Eccube\Repository\PageLayoutRepository;
use Eccube\Repository\Master\DeviceTypeRepository;

class PluginManager extends AbstractPluginManager
{
    // プラグインのコンフィグ
    const CONFIGID = 1;
    const DISPLAYBLOCK = 4;
    const DISPLAYPAGE = 8;
    const ENG_TITLE = "Blog";
    const JP_TITLE = "ブログ";
    const IMAGEPATH = 'cm_blog_pro/images';
    // ブロック
    const BLOCKNAME = '簡単ブログPro';
    const BLOCKFILENAME = "cm_blog_block_pro";
    const BLOCKFOLDERNAME = "/Block/";
    // ページ一覧
    const PAGELISTNAME = '簡単ブログPro一覧ページ';
    const PAGELISTFILENAME = "blog/list";
    const PAGELISTURL = "cm_blog_pro_page_list";
    // ページ
    const PAGEDETAILNAME = '簡単ブログPro詳細ページ';
    const PAGEDETAILFILENAME = "blog/detail";
    const PAGEDETAILURL = "cm_blog_pro_page_detail";

    private $del_row = 0;
    /**
     * Update the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function update(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $schemaManager = $em->getConnection()->getSchemaManager();
        // Blog のステータスの登録
        $this->initBlogStatus($em);

        $em->getConnection()->beginTransaction();
        try {

            $sql = <<<___SQL
SELECT *
FROM
plg_blog_category2
LIMIT 1
___SQL;
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $categoryData = $stmt->fetchAll();

            // 更新する時、テーブルに既存データがある場合バックアップは要らないのでスキップ
            //　はじめに更新するブログテーブルを見て判断
            if(count($categoryData) == 0) {

                $tblArr =  [
                    'backUpCategory'     => 'plg_blog_category',
                    'backUpBlog'         => 'plg_blog_data',
                    'backUpBlogImage'    => 'plg_blog_image',
                    'backUpBlogCategory' => 'plg_blog_blog_category',
                    'backUpBlogProduct'  => 'plg_blog_blog_product',
                    'backUpConfig'       => 'plg_blog_config',
                ];

                foreach($tblArr as $funcName=>$tblName) {

                    if($schemaManager->tablesExist(array($tblName)) == true) {

                        $this->backUpOldData($em, $container, $tblName, $funcName);
                    }
                }
            }

            $em->getConnection()->commit();

        } catch(Exception $e) {

            $em->getConnection()->rollback();
            // log error
        }

    }

    /**
     * ブログプローの古いテーブルのデータをバックアップする
     *
     * @param EntityManagerInterface $em
     * @param ContainerInterface $container
     * @param String $tblName
     * @param String $funcName
     */
    protected function backUpOldData(
        EntityManagerInterface $em,
        ContainerInterface $container,
        $tblName,
        $funcName)
    {
        $sql = <<<___SQL
SELECT *
FROM
$tblName
___SQL;

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        if("plg_blog_data" == $tblName || "plg_blog_category" == $tblName) {

            $sql = <<<___SQL
SELECT
MAX(id) as max_id
FROM
$tblName
___SQL;

                $stmt = $em->getConnection()->prepare($sql);
                $stmt->execute();
                $idArr = $stmt->fetchAll();
                $id = $idArr[0]['max_id'];

                for($i = 1; $i <= $id; $i++) {
                    
                    $key = array_search($i, array_column($result, 'id'));

                    if (false !== $key) {

                        $backUpData = $this->$funcName($container, $result[$key]);
                    } else {

                        $backUpData = $this->$funcName($container);
                    }

                    $this->saveObject($em, $backUpData);
                }
                $this->del_row = 0;
        } else {

            foreach($result as $res) {
            
                $backUpData = $this->$funcName($container, $res);
    
                $this->saveObject($em, $backUpData);
            }
        }
    }

    /**
     * plg_blog_blog_category →　plg_blog_blog_category2 に保存するためデータ準備
     *
     * @param array $data
     * @param ContainerInterface $container
     * 
     * @return Object $blogCategory
     */
    protected function backUpBlogCategory(ContainerInterface $container, $data)
    {
        $blogCategory = new BlogCategory();

        $entityManager = $container->get('doctrine')->getManager();
        $blogEntity    = $entityManager->getRepository(Blog::class)->find($data['blog_id']);
        $categoryEntity = $entityManager->getRepository(Category::class)->find($data['category_id']);

        $blogCategory
        ->setBlogId($data['blog_id'])
        ->setCategoryId($data['category_id'])
        ->setBlog($blogEntity)
        ->setCategory($categoryEntity);

        return $blogCategory;
    }

    /**
     * plg_blog_blog_product →　plg_blog_blog_product2 に保存するためデータ準備
     *
     * @param array $data
     * @param ContainerInterface $container
     * 
     * @return Object $blogProduct
     */
    protected function backUpBlogProduct(ContainerInterface $container, $data)
    {
        $blogProduct = new BlogProduct();

        $entityManager = $container->get('doctrine')->getManager();
        $blogEntity     = $entityManager->getRepository(Blog::class)->find($data['blog_id']);
        $productEntity  = $entityManager->getRepository(Product::class)->find($data['product_id']);

        $blogProduct
        ->setBlogId($data['blog_id'])
        ->setProductId($data['product_id'])
        ->setBlog($blogEntity)
        ->setProduct($productEntity);;

        return $blogProduct;
    }

    /**
     * plg_blog_image →　plg_blog_image2 に保存するためデータ準備
     *
     * @param array $data
     * @param ContainerInterface $container
     * 
     * @return Object $blogImage
     */
    protected function backUpBlogImage(ContainerInterface $container, $data)
    {
        $blogImage = new BlogImage();
        $entityManager = $container->get('doctrine')->getManager();
        
        $blogEntity = $entityManager->getRepository(Blog::class)->find($data['blog_id']);

        $blogImage
        ->setBlog($blogEntity)
        ->setFileName($data['file_name'])
        ->setSortNo($data['sort_no']);

        return $blogImage;
    }

    /**
     * plg_blog_category →　plg_blog_category2 に保存するためデータ準備
     *
     * @param array $data
     * @param ContainerInterface $container
     * 
     * @return Object $category
     */
    protected function backUpCategory(ContainerInterface $container, $data = array())
    {
        $category = new Category();
        if(empty($data)) {

            $this->del_row += 1;
            $category
            ->setName("削除カテゴリ" . $this->del_row);
        } else {

            $category
            ->setName($data['name'])
            ->setClass($data['class']);
        }

        return $category;
    }

    /**
     * plg_blog_data →　plg_blog_data2 に保存するためデータ準備
     *
     * @param array $data
     * @param ContainerInterface $container
     * 
     * @return Object $blog
     */
    protected function backUpBlog(ContainerInterface $container, $data = array())
    {
        $blog = new Blog();
        
        $entityManager = $container->get('doctrine')->getManager();
        if(empty($data)) {
            //削除したブログ行のため廃止ステータスで登録
            $statusEntity = $entityManager->getRepository(BlogStatus::class)->find(3);

            $blog
            ->setTitle("テストブログ")
            ->setStatus($statusEntity)
            ->setReleaseDate(new \DateTime());
        } else {

            $statusEntity = $entityManager->getRepository(BlogStatus::class)->find($data['blog_status_id']);

            $blog
            ->setTitle($data['title'])
            ->setStatus($statusEntity)
            ->setBody($data['body'])
            ->setAuthor($data['author'])
            ->setDescription($data['description'])
            ->setKeyword($data['keyword'])
            ->setRobot($data['meta_robots'])
            ->setMetatag($data['meta_tags'])
            ->setReleaseDate(new \DateTime($data['release_date']))
            ->setTag($data['tag']);
        }

        return $blog;
    }

    /**
     * plg_blog_config →　plg_blog_cpmfog2 に保存するためデータ準備
     *
     * @param array $data
     * @param ContainerInterface $container
     * 
     * @return Object $Config
     */
    protected function backUpConfig(ContainerInterface $container, $data)
    {
        $config = new Config();

        $config
        ->setDisplayBlock($data['display_block'])
        ->setDisplayPage($data['display_page'])
        ->setTitleEn($data['title_en'])
        ->setTitleJp($data['title_jp']);

        return $config;
    }

    /**
     * アンインストールの時、確実にテーブル・ページ情報などを削除
     *
     * @param $meta
     * @param ContainerInterface $container
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $em->getConnection()->beginTransaction();
        try {

            $tblArr =  [
                'plg_blog_blog_category2',
                'plg_blog_blog_product2',
                'plg_blog_image2',
                'plg_blog_category2',
                'plg_blog_data2',
                'plg_blog_mtb_status2',
                'plg_blog_config2'
            ];
            $this->deleteTable($em, $tblArr, $container);
    
            $this->removePage($em, $container);
    
            $this->removePageDetail($em, $container);
            $em->getConnection()->commit();

        } catch(Exception $e) {
            $em->getConnection()->rollback();
            // log error
        }
    }

    /**
     * ページリスト削除
     *
     * @param EntityManagerInterface $em
     * @param ContainerInterface $container
     */
    protected function removePage(
        EntityManagerInterface $em,
        ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $Page = $entityManager->getRepository(Page::class)->findOneBy(
            [
                'name'       => self::PAGELISTNAME,
                'file_name'  => self::PAGELISTFILENAME,
                'url'        => self::PAGELISTURL,
                'edit_type'  => PAGE::EDIT_TYPE_DEFAULT
            ]
        );
        if ($Page) {

            $Layout = $entityManager->getRepository('Eccube\Entity\Layout')->find(2);

            $PageLayout = $entityManager->getRepository(PageLayout::class)->findOneBy(['Page' => $Page, 'Layout' => $Layout]); // Blockの削除 $entityManager = $container->get('doctrine')->getManager(); 

            $this->removeObject($em, $PageLayout);
            $this->removeObject($em, $Page);
        }
    }

    /**
     * ページ詳細削除
     *
     * @param EntityManagerInterface $em
     * @param ContainerInterface $container
     */
    protected function removePageDetail(
        EntityManagerInterface $em,
        ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $PageDetail = $entityManager->getRepository(Page::class)->findOneBy(
            [
                'name'       => self::PAGEDETAILNAME,
                'file_name'  => self::PAGEDETAILFILENAME,
                'url'        => self::PAGEDETAILURL,
                'edit_type'  => PAGE::EDIT_TYPE_DEFAULT
            ]
        );
        if ($PageDetail) {

            $Layout = $entityManager->getRepository('Eccube\Entity\Layout')->find(2);
            $PageLayout = $entityManager->getRepository(PageLayout::class)->findOneBy(['Page' => $PageDetail, 'Layout' => $Layout]); // Blockの削除 $entityManager = $container->get('doctrine')->getManager(); 
            
            $this->removeObject($em, $PageLayout);
            $this->removeObject($em, $PageDetail);
        }
    }

    /**
     * テーブルの削除
     *
     * @param EntityManagerInterface $em
     * @param ContainerInterface $container
     */
    public function deleteTable(EntityManagerInterface $em, array $tblArr, ContainerInterface $container) {
        
        foreach($tblArr as $tblName) {

            $sql = 'DROP TABLE if exists ' . $tblName;
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        }
    }

    /**
     * プラグイン有効時の処理
     *
     * @param $meta
     * @param ContainerInterface $container
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');
        $em->getConnection()->beginTransaction();
        try {
            $Config = $this->initConfig($em);
            $ListPage = $this->initListPage($em, $container);
            $ListPageLayout = $this->initPageLayout($em, $container, $ListPage);
            $DetailPage = $this->initDetailPage($em, $container);
            $DetailPageLayout = $this->initPageLayout($em, $container, $DetailPage);
            $Block = $this->initBlock($em, $container);
            $this->initBlogStatus($em);
            $em->getConnection()->commit();
        } catch(Exception $e) {
            $em->getConnection()->rollback();
            // log error
        }
    }

    /**
     * プラグイン無効時の処理
     *
     * @param $meta
     * @param ContainerInterface $container
     */
    public function disable(array $meta, ContainerInterface $container)
    {
        // データを残す
        // レイアウトから削除する
        // ブロックを削除する
        // $em = $container->get('doctrine.orm.entity_manager');
        // $Block = $this->deleteBlock($em, $container);
        // コントローラを削除する
        // ページを削除する
    }

    /**
     * ブロックを追加
     * 
     * @param EntityManagerInterface $em
     * @param Model $obj
     */
    private function saveObject(EntityManagerInterface $em, $obj)
    {
        $em->persist($obj);
        $em->flush($obj);
        return $obj;
    }

    /**
     * ブロックを削除
     * 
     * @param EntityManagerInterface $em
     * @param Model $obj
     */
    private function removeObject(EntityManagerInterface $em, $obj)
    {
        $em->remove($obj);
        $em->flush($obj);
        return $obj;
    }

    /**
     * テンプレート作成
     *
     * @param str $templateDir // 現在のテーマのテンプレートにファイルを配置するため現在のテーマのディレクトリを取得します
     * @param str $folder
     * @param str $filename
     */
    private function createTemplate($templateDir, $folder, $filename)
    {
        $sourceFile = __DIR__.'/Resource/template/'.$filename.'.twig';

        // コピー先にファイルがない場合のみファイルをコピーします
        $file = new Filesystem();

        if (!$file->exists($templateDir.$folder.$filename.'.twig')) {
            // app/template配下へブロックのtwigファイルを配置
            $file->copy($sourceFile, $templateDir.$folder.$filename.'.twig');
        } else {

            if (unlink($templateDir.$folder.$filename.'.twig')) {
                $file->copy($sourceFile, $templateDir.$folder.$filename.'.twig');
            }
        }
    }

    /**
     * ページを追加
     *
     * @param EntityManagerInterface $em
     * @param ContainerInterface $container
     */
    protected function initListPage(
        EntityManagerInterface $em,
        ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $Page = $entityManager->getRepository(Page::class)->findOneBy(
            [
                'name'       => self::PAGELISTNAME,
                'file_name'  => self::PAGELISTFILENAME,
                'url'        => self::PAGELISTURL,
                'edit_type'  => PAGE::EDIT_TYPE_DEFAULT
            ]
        );
        if ($Page) {
            // 既にあるデータと何もしない
            $this->createTemplate(
                $container->getParameter('eccube_theme_front_dir'),
                '/', self::PAGELISTFILENAME);
            return $Page;
        }
        // 新しいデータを作成する
        $Page = new Page();
        $Page
            ->setUrl(self::PAGELISTURL)
            ->setFileName(self::PAGELISTFILENAME)
            ->setName(self::PAGELISTNAME)
            ->setEditType(PAGE::EDIT_TYPE_DEFAULT);
        $this->createTemplate(
            $container->getParameter('eccube_theme_front_dir'),
            '/', self::PAGELISTFILENAME);
        return $this->saveObject($em, $Page);
    }

    /**
     * ページ一覧Layoutを追加
     *
     * @param EntityManagerInterface $em
     * @param ContainerInterface $container
     */
    protected function initPageLayout(
        EntityManagerInterface $em,
        ContainerInterface $container,
        Page $page)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $PageLayout = $entityManager->getRepository(PageLayout::class)->findOneBy(
            [
                'page_id'    => $page->getId()
            ]
        );
        if ($PageLayout) {
            // 既にあるデータと何もしない
            return $PageLayout;
        }
        // 新しいデータを作成する
        $Layout = $entityManager->getRepository('Eccube\Entity\Layout')->find(2);
        $PageLayout = new PageLayout();
        $PageLayout->setPageId($page->getId());
        $PageLayout->setPage($page);
        $PageLayout->setLayoutId(2);
        $PageLayout->setLayout($Layout);
        $PageLayout->setSortNo($page->getId());
        return $this->saveObject($em, $PageLayout);
    }

    /**
     * ページ詳細を追加
     *
     * @param EntityManagerInterface $em
     * @param ContainerInterface $container
     */
    protected function initDetailPage(
        EntityManagerInterface $em,
        ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $Page = $entityManager->getRepository(Page::class)->findOneBy(
            [
                'name'       => self::PAGEDETAILNAME,
                'file_name'  => self::PAGEDETAILFILENAME,
                'url'        => self::PAGEDETAILURL,
                'edit_type'  => PAGE::EDIT_TYPE_DEFAULT
            ]
        );
        if ($Page) {
            // 既にあるデータと何もしない
            $this->createTemplate(
                $container->getParameter('eccube_theme_front_dir'),
                '/', self::PAGEDETAILFILENAME);
            return $Page;
        }
        // 新しいデータを作成する
        $Page = new Page();
        $Page
            ->setUrl(self::PAGEDETAILURL)
            ->setFileName(self::PAGEDETAILFILENAME)
            ->setName(self::PAGEDETAILNAME)
            ->setEditType(PAGE::EDIT_TYPE_DEFAULT);
        $this->createTemplate(
            $container->getParameter('eccube_theme_front_dir'),
            '/', self::PAGEDETAILFILENAME);
        return $this->saveObject($em, $Page);
    }

    /**
     * ブロックを追加
     *
     * @param EntityManagerInterface $em
     * @param ContainerInterface $container
     */
    protected function initBlock(
        EntityManagerInterface $em,
        ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $DeviceType = $entityManager->getRepository(DeviceType::class)
            ->find(DeviceType::DEVICE_TYPE_PC);

        $Block = $entityManager->getRepository(Block::class)->findOneBy(
            [
                'name'       => self::BLOCKNAME,
                'file_name'  => self::BLOCKFILENAME,
                'DeviceType' => $DeviceType
            ]
        );
        if ($Block) {

            // 既にあるデータと何もしない
            $this->createTemplate(
                $container->getParameter('eccube_theme_front_dir'),
                self::BLOCKFOLDERNAME,
                self::BLOCKFILENAME);
            return $Block;
        }
        // 新しいデータを作成する
        $Block = new Block();
        $Block->setName(self::BLOCKNAME);
        $Block->setFileName(self::BLOCKFILENAME);
        $Block->setUseController(Constant::DISABLED);
        $Block->setDeletable(Constant::DISABLED);
        $Block->setDeviceType($DeviceType);
        $this->createTemplate(
            $container->getParameter('eccube_theme_front_dir'),
            self::BLOCKFOLDERNAME,
            self::BLOCKFILENAME);
        return $this->saveObject($em, $Block);
    }

    /**
     * プラグイン設定を追加
     *
     * @param EntityManagerInterface $em
     */
    protected function initConfig(EntityManagerInterface $em)
    {
        $Config = $em->find(Config::class, self::CONFIGID);
        if ($Config) {
            // 既にあるデータをリセットする
            return $this->saveObject($em, $Config);
        }
        // 新しいデータを作成する
        $Config = new Config();
        $Config->setDisplayBlock(self::DISPLAYBLOCK);
        $Config->setDisplayPage(self::DISPLAYPAGE);
        $Config->setTitleEn(self::ENG_TITLE);
        $Config->setTitleJp(self::JP_TITLE);
        return $this->saveObject($em, $Config);
    }

    /**
     * ブログステータスマスタ
     *
     * @param EntityManagerInterface $em
     */
    protected function initBlogStatus(EntityManagerInterface $em)
    {
        $sql = "INSERT into plg_blog_mtb_status2 VALUES(:id, :name, :sort, :type)";
        $stmt = $em->getConnection()->prepare($sql);

        $params = array(
            "id"    => 1,
            "name"  => "公開",
            "sort"  => 0,
            "type"  => "blogstatus"
        );
        $this->_saveBlogStatus($em, $stmt, $params);

        $params = array(
            "id"    => 2,
            "name"  => "非公開",
            "sort"  => 1,
            "type"  => "blogstatus"
        );
        $this->_saveBlogStatus($em, $stmt, $params);

        $params = array(
            "id"    => 3,
            "name"  => "廃止",
            "sort"  => 2,
            "type"  => "blogstatus"
        );
        $this->_saveBlogStatus($em, $stmt, $params);
    }

    /**
     * ブロックを削除する
     *
     * @param EntityManagerInterface $em
     * @param ContainerInterface $container
     */
    protected function deleteBlock(
        EntityManagerInterface $em,
        ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $DeviceType = $entityManager->getRepository(DeviceType::class)
            ->find(DeviceType::DEVICE_TYPE_PC);

        $Block = $entityManager->getRepository(Block::class)->findOneBy(
            [
                'name'       => self::BLOCKNAME,
                'file_name'  => self::BLOCKFILENAME,
                'DeviceType' => $DeviceType,
            ]
        );
        if ($Block) {
            $em->remove($Block);
            $em->flush();
        }
    }

    /**
     * ブログステータス保存処理
     *
     * @param EntityManagerInterface $em
     */
    private function _saveBlogStatus(EntityManagerInterface $em, $stmt, $params = array()) {

        $statusInfo = $em->find(BlogStatus::class, $params['id']);
        if ($statusInfo) {
            return;
        }
        
        $stmt->execute($params);
    }
}
