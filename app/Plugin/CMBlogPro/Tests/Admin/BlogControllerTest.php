<?php
namespace Plugin\CMBlogPro\Tests\Admin;

use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Faker\Generator;
use Plugin\CMBlogPro\Entity\Blog;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class CMBlogProBlogControllerTest.
 */
class CMBlogProBlogControllerTest extends AbstractAdminWebTestCase
{
    /**
     * @var Generator
     */
    protected $faker;

    /**
     * テストデータを準備する
     *
     * @return Blog
     */
    private function createBlog()
    {        
        $Blog = new Blog();
        $Blog->setTitle($this->faker->text(10));
        $Blog->setBody($this->faker->text);

        $this->entityManager->persist($Blog);
        $this->entityManager->flush($Blog);

        return $Blog;
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
    public function testBlogList()
    {
        /**
         * @var Client
         */
        $client = $this->client;
        /**
         * @var Crawler
         */
        $crawler = $this->client->request('GET', $this->generateUrl('cm_blog_admin_blog'));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertContains('ブログ一覧', $crawler->html());
    }

    /**
     * カテゴリ作成 
     */
    public function testBlogCreate()
    {
        /**
         * @var Client
         */
        $client = $this->client;
        /**
         * @var Crawler
         */
        $crawler = $this->client->request('GET', $this->generateUrl('cm_blog_admin_blog_create'));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $form = $crawler->selectButton('登録')->form();
        $form['blog[title]'] = $this->faker->text(20);
        $form['blog[body]'] = $this->faker->text;
        $crawler = $client->submit($form);
        $crawler = $client->followRedirect();
        $this->assertContains('登録しました。', $crawler->html());
    }

    /**
     * カテゴリ編集
     */
    public function testBlogEdit()
    {
        /**
         * @var Client
         */
        $client = $this->client;
        $Blog = $this->createBlog();
        /**
         * @var Crawler
         */
        $crawler = $this->client->request('GET',
            $this->generateUrl('cm_blog_admin_blog_edit', ['id' => $Blog->getId()]));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $form = $crawler->selectButton('登録')->form();
        $form['blog[title]'] = $this->faker->text(20);
        $form['blog[body]'] = $this->faker->text;
        $crawler = $client->submit($form);
        $crawler = $client->followRedirect();
        $this->assertContains('登録しました。', $crawler->html());
    }

    /**
     * カテゴリ削除
     */
    public function testBlogDelete()
    {
        /**
         * @var Client
         */
        $client = $this->client;
        $Blog = $this->createBlog();
        /**
         * @var Crawler
         */
        $crawler = $this->client->request('GET',
            $this->generateUrl('cm_blog_admin_blog_delete', ['id' => $Blog->getId()]));
        $crawler = $client->followRedirect();
        $this->assertContains('ブログを削除しました', $crawler->html());
    }
}