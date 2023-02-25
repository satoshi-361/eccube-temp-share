<?php
namespace Plugin\CMBlogPro\Tests\Admin;

use Faker\Generator;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\DomCrawler\Crawler;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;

/**
 * Class CMBlogProConfigControllerTest.
 */
class CMBlogProConfigControllerTest extends AbstractAdminWebTestCase
{
    /**
     * @var Generator
     */
    protected $faker;

    /**
     * セットアップ
     */
    public function setUp(){
        parent::setUp();
        $this->faker = $this->getFaker();
    }

    /**
     * プラグイン設定のtwig表示テスト
     */
    public function testConfigRouting()
    {
        /**
         * @var Client
         */
        $client = $this->client;
        /**
         * @var Crawler
         */
        $crawler = $this->client->request('GET', $this->generateUrl('cm_blog_pro_admin_config'));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertContains('ブロックに表示する記事数', $crawler->html());
    }

    /**
     * プラグイン設定のテスト
     */
    public function testConfigSuccess()
    {
        /**
        * @var Client
        */
        $client = $this->client;
        /**
        * @var Crawler
        */
        $crawler = $this->client->request('GET', $this->generateUrl('cm_blog_pro_admin_config'));
        $form = $crawler->selectButton('登録')->form();
        $form['config[display_block]'] = $this->faker->randomNumber(1);
        $form['config[display_page]'] = $this->faker->randomNumber(1);
        $form['config[image_path]'] = 'mytest/images';
        $crawler = $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirection($this->generateUrl('cm_blog_pro_admin_config')));
        $crawler = $client->followRedirect();
        $this->assertContains('登録しました。', $crawler->html());
    }

    // TODO: test for plugin enable
    // TODO: test for plugin disable
}
