<?php
namespace Plugin\CMBlogPro\Tests\Blog;

use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Faker\Generator;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Webのページに関するテスト 
 */
class PageControllerTest extends AbstractAdminWebTestCase
{
    public function testEnable() {
        $this->assertTrue(true);
    }

    public function testDisable() {
        $this->assertTrue(true);
    }
}
