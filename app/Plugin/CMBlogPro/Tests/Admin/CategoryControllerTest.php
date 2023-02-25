<?php
namespace Plugin\CMBlogPro\Tests\Admin;

use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Faker\Generator;
use Plugin\CMBlogPro\Entity\Category;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class CMBlogProCategoryControllerTest.
 */
class CMBlogProCategoryControllerTest extends AbstractAdminWebTestCase
{
    /**
     * @var Generator
     */
    protected $faker;

    /**
     * テストデータを準備する
     *
     * @return Category
     */
    private function createCategory()
    {        
        $category = new Category();
        $category->setName($this->faker->text(10));

        $this->entityManager->persist($category);
        $this->entityManager->flush($category);

        return $category;
    }

    /**
     * セットアップ
     */
    public function setUp(){
        parent::setUp();
        $this->faker = $this->getFaker();
    }

    /**
     * カテゴリ一覧
     */
    public function testCategoryList()
    {
        /**
         * @var Client
         */
        $client = $this->client;
        /**
         * @var Crawler
         */
        $crawler = $this->client->request('GET', $this->generateUrl('cm_blog_admin_cat'));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertContains('カテゴリ一覧', $crawler->html());
    }

    /**
     * カテゴリ作成 
     */
    public function testCategoryCreate()
    {
        /**
         * @var Client
         */
        $client = $this->client;
        /**
         * @var Crawler
         */
        $crawler = $this->client->request('GET', $this->generateUrl('cm_blog_admin_cat_create'));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $form = $crawler->selectButton('登録')->form();
        $form['category[name]'] = $this->faker->text(10);
        $crawler = $client->submit($form);
        $crawler = $client->followRedirect();
        $this->assertContains('登録しました。', $crawler->html());
    }

    /**
     * カテゴリ編集
     */
    public function testCategoryEdit()
    {
        /**
         * @var Client
         */
        $client = $this->client;
        $category = $this->createCategory();
        /**
         * @var Crawler
         */
        $crawler = $this->client->request('GET',
            $this->generateUrl('cm_blog_admin_cat_edit', ['id' => $category->getId()]));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $form = $crawler->selectButton('登録')->form();
        $form['category[name]'] = $this->faker->text(10);
        $crawler = $client->submit($form);
        $crawler = $client->followRedirect();
        $this->assertContains('登録しました。', $crawler->html());
    }

    /**
     * カテゴリ削除
     */
    public function testCategoryDelete()
    {
        /**
         * @var Client
         */
        $client = $this->client;
        $category = $this->createCategory();
        /**
         * @var Crawler
         */
        $crawler = $this->client->request('GET',
            $this->generateUrl('cm_blog_admin_cat_delete', ['id' => $category->getId()]));
        $crawler = $client->followRedirect();
        $this->assertContains('カテゴリを削除しました', $crawler->html());
    }
}