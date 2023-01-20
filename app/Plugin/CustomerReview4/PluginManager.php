<?php

namespace Plugin\CustomerReview4;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Csv;
use Eccube\Entity\Layout;
use Eccube\Entity\Master\CsvType;
use Eccube\Entity\Master\ProductListOrderBy;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\PageRepository;
use Plugin\CustomerReview4\Entity\CustomerReviewConfig;
use Plugin\CustomerReview4\Entity\CustomerReviewStatus;
use Plugin\CustomerReview4\Entity\CustomerReviewTotal;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginManager extends AbstractPluginManager
{
    /**
     * @var array
     */
    private $urls = array(
        array(
            'page_name' => 'レビューを投稿',
            'url'  => 'review_post',
            'file_name'  => '@CustomerReview4/post',
        ),
        array(
            'page_name' => 'レビューを投稿(確認)',
            'url'  => 'review_confirm',
            'file_name'  => '@CustomerReview4/confirm',
        ),
        array(
            'page_name' => 'レビューを投稿(完了)',
            'url'  => 'review_complete',
            'file_name'  => '@CustomerReview4/complete',
        ),
        array(
            'page_name' => 'レビュー投稿済み',
            'url'  => 'review_posted',
            'file_name'  => '@CustomerReview4/posted',
        ),
        array(
            'page_name' => 'レビュー一覧',
            'url'  => 'review_list',
            'file_name'  => '@CustomerReview4/list',
        ),
    );

    /**
     * Enable the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();

        // プラグイン設定を追加
        $Config = $this->createConfig($entityManager);

        // レビューステータス(公開・非公開)を追加
        $this->createStatus($entityManager);

        // CSV出力項目設定を追加
        $CsvType = $Config->getCsvType();
        if (null === $CsvType) {
            $CsvType = $this->createCsvType($entityManager);
            $this->createCsvData($entityManager, $CsvType);

            $Config->setCsvType($CsvType);
            $entityManager->flush($Config);
        }

        $pageRepository = $entityManager->getRepository(\Eccube\Entity\Page::class);

        // ページを追加
        foreach ($this->urls as $url) {
            $Page = $pageRepository->findOneBy(['url' => $url['url']]);
            if (null === $Page) {
                $this->createPage($entityManager, $url);
            }
        }

        // ソート追加
        $this->createProductListOrderBy($entityManager);
    }

    /**
     * Disable the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function disable(array $meta, ContainerInterface $container)
    {
        // ソート削除
        $this->removeProductListOrderBy($container->get('doctrine')->getManager());
    }

    /**
     * Uninstall the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();

        // ページを削除
        foreach ($this->urls as $url) {
            $this->removePage($entityManager, $url);
        }

        // CSV出力項目設定を削除
        $Config = $entityManager->find(CustomerReviewConfig::class, 1);
        if ($Config) {
            $CsvType = $Config->getCsvType();

            $this->removeCsvData($entityManager, $CsvType);

            $Config->setCsvType(null);
            $entityManager->flush($Config);

            $entityManager->remove($CsvType);
            $entityManager->flush($CsvType);
        }
    }

    protected function createConfig(EntityManagerInterface $entityManager)
    {
        $Config = $entityManager->find(CustomerReviewConfig::class, 1);
        if ($Config) {
            return $Config;
        }
        $Config = new CustomerReviewConfig();
        $Config->setReviewMax(20);
        $Config->setGrantPoint(0);
        $Config->setGrantPointPurchase(false);
        $Config->setLoginOnly(false);
        $Config->setDetailInReview(true);
        $Config->setPurchaseMark(true);
        $Config->setDefaultReviewerName('ななし');

        $entityManager->persist($Config);
        $entityManager->flush($Config);

        return $Config;
    }

    protected function createStatus(EntityManagerInterface $entityManager)
    {
        $Status = $entityManager->find(CustomerReviewStatus::class, 1);
        if ($Status) {
            return;
        }

        $Status = new CustomerReviewStatus();
        $Status->setId(1);
        $Status->setName('新規投稿');
        $Status->setSortNo(1);

        $entityManager->persist($Status);
        $entityManager->flush($Status);

        $Status = new CustomerReviewStatus();
        $Status->setId(2);
        $Status->setName('承認');
        $Status->setSortNo(2);

        $entityManager->persist($Status);
        $entityManager->flush($Status);

        $Status = new CustomerReviewStatus();
        $Status->setId(3);
        $Status->setName('非承認');
        $Status->setSortNo(3);

        $entityManager->persist($Status);
        $entityManager->flush($Status);
    }

    protected function createProductListOrderBy(EntityManagerInterface $entityManager)
    {
        $result = $entityManager->createQueryBuilder('plob')
            ->select('COALESCE(MAX(plob.sort_no), 0) AS sort_no')
            ->from(ProductListOrderBy::class, 'plob')
            ->getQuery()
            ->getSingleResult();

        $ProductListOrderBy = $entityManager->getRepository(ProductListOrderBy::class)->findOneBy(['id' => CustomerReviewTotal::SORT_RECOMMEND_HIGHER]);
        if ( !$ProductListOrderBy ) {
            $result['sort_no']++;
            $ProductListOrderBy = new ProductListOrderBy();
            $ProductListOrderBy
                ->setId(CustomerReviewTotal::SORT_RECOMMEND_HIGHER)
                ->setName('評価の高い順')
                ->setSortNo($result['sort_no']);
            $entityManager->persist($ProductListOrderBy);
            $entityManager->flush($ProductListOrderBy);
        }

        $ProductListOrderBy = $entityManager->getRepository(ProductListOrderBy::class)->findOneBy(['id' => CustomerReviewTotal::SORT_RECOMMEND_OUTNUMBER]);
        if ( !$ProductListOrderBy ) {
            $result['sort_no']++;
            $ProductListOrderBy = new ProductListOrderBy();
            $ProductListOrderBy
                ->setId(CustomerReviewTotal::SORT_RECOMMEND_OUTNUMBER)
                ->setName('評価の多い順')
                ->setSortNo($result['sort_no']);
            $entityManager->persist($ProductListOrderBy);
            $entityManager->flush($ProductListOrderBy);
        }
    }

    protected function removeProductListOrderBy(EntityManagerInterface $entityManager)
    {
        $ProductListOrderBy = $entityManager->getRepository(ProductListOrderBy::class)->findOneBy(['id' => CustomerReviewTotal::SORT_RECOMMEND_HIGHER]);
        if ( $ProductListOrderBy ) {
            $entityManager->remove($ProductListOrderBy);
            $entityManager->flush($ProductListOrderBy);
        }

        $ProductListOrderBy = $entityManager->getRepository(ProductListOrderBy::class)->findOneBy(['id' => CustomerReviewTotal::SORT_RECOMMEND_OUTNUMBER]);
        if ( $ProductListOrderBy ) {
            $entityManager->remove($ProductListOrderBy);
            $entityManager->flush($ProductListOrderBy);
        }
    }

    protected function createPage(EntityManagerInterface $entityManager, $url)
    {
        $Page = new Page();
        $Page->setEditType(Page::EDIT_TYPE_DEFAULT);
        $Page->setName($url['page_name']);
        $Page->setUrl($url['url']);
        $Page->setFileName($url['file_name']);

        // DB登録
        $entityManager->persist($Page);
        $entityManager->flush($Page);
        $Layout = $entityManager->find(Layout::class, Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);
        $PageLayout = new PageLayout();
        $PageLayout->setPage($Page)
            ->setPageId($Page->getId())
            ->setLayout($Layout)
            ->setLayoutId($Layout->getId())
            ->setSortNo(0);
        $entityManager->persist($PageLayout);
        $entityManager->flush($PageLayout);
    }

    protected function removePage(EntityManagerInterface $entityManager, $url)
    {
        $Page = $entityManager->getRepository(Page::class)->findOneBy(['url' => $url['url']]);

        if (!$Page) {
            return;
        }
        foreach ($Page->getPageLayouts() as $PageLayout) {
            $entityManager->remove($PageLayout);
            $entityManager->flush($PageLayout);
        }

        $entityManager->remove($Page);
        $entityManager->flush($Page);
    }

    protected function createCsvType(EntityManagerInterface $entityManager)
    {
        $result = $entityManager->createQueryBuilder('ct')
            ->select('COALESCE(MAX(ct.id), 0) AS id, COALESCE(MAX(ct.sort_no), 0) AS sort_no')
            ->from(CsvType::class, 'ct')
            ->getQuery()
            ->getSingleResult();

        $result['id']++;
        $result['sort_no']++;

        $CsvType = new CsvType();
        $CsvType
            ->setId($result['id'])
            ->setName('カスタマーレビューCSV')
            ->setSortNo($result['sort_no']);
        $entityManager->persist($CsvType);
        $entityManager->flush($CsvType);

        return $CsvType;
    }

    protected function createCsvData(EntityManagerInterface $entityManager, CsvType $CsvType)
    {
        $rank = 0;

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('Plugin\CustomerReview4\Entity\CustomerReviewList')
            ->setFieldName('id')
            ->setReferenceFieldName('id')
            ->setDispName('レビューID')
            ->setSortNo($rank);
        $entityManager->persist($Csv);
        $entityManager->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('Plugin\CustomerReview4\Entity\CustomerReviewList')
            ->setFieldName('Product')
            ->setReferenceFieldName('id')
            ->setDispName('商品ID')
            ->setSortNo($rank);
        $entityManager->persist($Csv);
        $entityManager->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('Plugin\CustomerReview4\Entity\CustomerReviewList')
            ->setFieldName('Product')
            ->setReferenceFieldName('name')
            ->setDispName('商品名')
            ->setSortNo($rank);
        $entityManager->persist($Csv);
        $entityManager->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('Plugin\CustomerReview4\Entity\CustomerReviewList')
            ->setFieldName('purchase')
            ->setReferenceFieldName('purchase')
            ->setDispName('購入済み')
            ->setSortNo($rank);
        $entityManager->persist($Csv);
        $entityManager->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('Plugin\CustomerReview4\Entity\CustomerReviewList')
            ->setFieldName('Customer')
            ->setReferenceFieldName('id')
            ->setDispName('会員ID')
            ->setSortNo($rank);
        $entityManager->persist($Csv);
        $entityManager->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('Plugin\CustomerReview4\Entity\CustomerReviewList')
            ->setFieldName('Customer')
            ->setReferenceFieldName('name01')
            ->setDispName('お名前(姓)')
            ->setSortNo($rank);
        $entityManager->persist($Csv);
        $entityManager->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('Plugin\CustomerReview4\Entity\CustomerReviewList')
            ->setFieldName('Customer')
            ->setReferenceFieldName('name02')
            ->setDispName('お名前(名)')
            ->setSortNo($rank);
        $entityManager->persist($Csv);
        $entityManager->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('Plugin\CustomerReview4\Entity\CustomerReviewList')
            ->setFieldName('reviewer_name')
            ->setReferenceFieldName('reviewer_name')
            ->setDispName('レビュアー名')
            ->setSortNo($rank);
        $entityManager->persist($Csv);
        $entityManager->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('Plugin\CustomerReview4\Entity\CustomerReviewList')
            ->setFieldName('recommend_level')
            ->setReferenceFieldName('recommend_level')
            ->setDispName('お勧め度')
            ->setSortNo($rank);
        $entityManager->persist($Csv);
        $entityManager->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('Plugin\CustomerReview4\Entity\CustomerReviewList')
            ->setFieldName('title')
            ->setReferenceFieldName('title')
            ->setDispName('タイトル')
            ->setSortNo($rank);
        $entityManager->persist($Csv);
        $entityManager->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('Plugin\CustomerReview4\Entity\CustomerReviewList')
            ->setFieldName('comment')
            ->setReferenceFieldName('comment')
            ->setDispName('コメント')
            ->setSortNo($rank);
        $entityManager->persist($Csv);
        $entityManager->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('Plugin\CustomerReview4\Entity\CustomerReviewList')
            ->setFieldName('Status')
            ->setReferenceFieldName('name')
            ->setDispName('承認状況')
            ->setSortNo($rank);
        $entityManager->persist($Csv);
        $entityManager->flush();

        $Csv = new Csv();
        ++$rank;
        $Csv->setCsvType($CsvType)
            ->setEntityName('Plugin\CustomerReview4\Entity\CustomerReviewList')
            ->setFieldName('create_date')
            ->setReferenceFieldName('create_date')
            ->setDispName('投稿日')
            ->setSortNo($rank);
        $entityManager->persist($Csv);
        $entityManager->flush();

        return $CsvType;
    }

    protected function removeCsvData(EntityManagerInterface $entityManager, CsvType $CsvType)
    {
        $CsvData = $entityManager->getRepository(Csv::class)->findBy(['CsvType' => $CsvType]);
        foreach ($CsvData as $Csv) {
            $entityManager->remove($Csv);
            $entityManager->flush($Csv);
        }
    }

}