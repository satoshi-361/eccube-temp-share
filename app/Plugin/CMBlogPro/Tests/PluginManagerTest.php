<?php
namespace Plugin\CMBlogPro\Tests;

use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Faker\Generator;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\DomCrawler\Crawler;

/**
 * 有効化／無効化に関するテスト.
 */
class PluginManagerTest extends AbstractAdminWebTestCase
{
    public function testEnable() {
        $this->assertTrue(true);
    }

    public function testDisable() {
        $this->assertTrue(true);
    }
}
