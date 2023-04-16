<?php
namespace Plugin\JsysAsi\Tests\Web\Admin;

use Faker\Generator;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\JsysAsi\Repository\ConfigRepository;
use Plugin\JsysAsi\Service\JsysAsiCryptService;

class JsysAsiConfigControllerTest extends AbstractAdminWebTestCase
{
    /**
     * @var Generator
     */
    protected $faker;

    /**
     * @var ConfigRepository
     */
    private $configRepo;

    /**
     * @var JsysAsiCryptService
     */
    private $service;


    /**
     * Setup method.
     * {@inheritDoc}
     * @see \Eccube\Tests\Web\Admin\AbstractAdminWebTestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();

        $this->faker      = $this->getFaker();
        $this->configRepo = self::$container->get(ConfigRepository::class);
        $this->service    = self::$container->get(JsysAsiCryptService::class);
    }

    /**
     * Config routing.
     */
    public function testRouting()
    {
        // プラグイン設定画面へ飛べるか
        $crawler = $this->client->request('GET', $this->generateUrl(
            'jsys_asi_admin_config'
        ));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // '2要素認証'が含まれているか
        $this->assertContains('2要素認証', $crawler->html());
    }

    /**
     * ajax用マスターキー生成メソッドテスト
     */
    public function testCreateMasterKey()
    {
        // マスターキー生成へアクセスできるか
        $this->client->request('GET', $this->generateUrl(
            'jsys_asi_admin_config_ajax_create_master_key'
        ));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // jsonで表示された値を配列へ
        $json = json_decode($this->client->getResponse()->getContent(), true);

        // 空配列でないか
        $this->assertNotEmpty($json);
        // 1件だけか
        $this->assertCount(1, $json);
        // マスターキーが存在するか
        $this->assertArrayHasKey('masterKey', $json);
        // マスターキーの値が32桁の文字列か
        $actual = $json['masterKey'];
        $this->assertIsString($actual);
        $this->assertSame(32, strlen($actual));
    }

    /**
     * マスターキー必須チェック
     */
    public function testMasterKeyRequire()
    {
        // フォームデータをPOST
        $formData = [
            '_token'                       => 'dummy',
            'option_tfa'                   => true,
            'option_tfa_master_key'        => null,
            'option_login_success_mail'    => false,
            'option_login_failure_mail'    => false,
            'option_ip_address_lock'       => false,
            'option_ip_address_lock_count' => null,
        ];
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('jsys_asi_admin_config'),
            ['config' => $formData]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エラーメッセージが含まれているか
        $error = $crawler
            ->filter('#config_option_tfa_master_key + .invalid-feedback')
            ->html();
        $this->assertContains('入力されていません。', $error);
    }

    /**
     * ロックまでの回数必須チェック
     */
    public function testLockCountRequire()
    {
        // フォームデータをPOST
        $formData = [
            '_token'                       => 'dummy',
            'option_tfa'                   => false,
            'option_tfa_master_key'        => null,
            'option_login_success_mail'    => false,
            'option_login_failure_mail'    => false,
            'option_ip_address_lock'       => true,
            'option_ip_address_lock_count' => null,
        ];
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('jsys_asi_admin_config'),
            ['config' => $formData]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // エラーメッセージが含まれているか
        $error = $crawler
            ->filter('#config_option_ip_address_lock_count + .invalid-feedback')
            ->html();
        $this->assertContains('入力されていません。', $error);
    }

    /**
     * ロックまでの回数0チェック
     */
    public function testLockCountZero()
    {
        // フォームデータをPOST
        $formData = [
            '_token'                       => 'dummy',
            'option_tfa'                   => false,
            'option_tfa_master_key'        => null,
            'option_login_success_mail'    => false,
            'option_login_failure_mail'    => false,
            'option_ip_address_lock'       => true,
            'option_ip_address_lock_count' => 0,
        ];
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('jsys_asi_admin_config'),
            ['config' => $formData]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 必須エラーメッセージが含まれているか
        $error = $crawler
            ->filter('#config_option_ip_address_lock_count + .invalid-feedback')
            ->html();
        $this->assertContains('1以上でなければなりません。', $error);
    }

    /**
     * デフォルト値更新チェック
     */
    public function testUpdateByDefaultValue()
    {
        // フォームデータをPOST
        $formData = [
            '_token'                       => 'dummy',
            'option_tfa'                   => false,
            'option_tfa_master_key'        => null,
            'option_login_success_mail'    => false,
            'option_login_failure_mail'    => false,
            'option_ip_address_lock'       => false,
            'option_ip_address_lock_count' => null,
        ];
        $this->client->request(
            'POST',
            $this->generateUrl('jsys_asi_admin_config'),
            ['config' => $formData]
        );

        // 設定画面にリダイレクトされたか
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl(
            'jsys_asi_admin_config'
        )));
        $crawler = $this->client->followRedirect();

        // 登録済みメッセージが含まれているか
        $this->assertContains('登録しました。', $crawler->html());

        // DBからレコードを取得し、同じ値かチェック
        $Config = $this->configRepo->get();
        $this->assertSame($formData['option_tfa'], $Config->getOptionTfa());
        $this->assertSame(
            $formData['option_tfa_master_key'],
            $Config->getOptionTfaMasterKey()
        );
        $this->assertSame(
            $formData['option_login_success_mail'],
            $Config->getOptionLoginSuccessMail()
        );
        $this->assertSame(
            $formData['option_login_failure_mail'],
            $Config->getOptionLoginFailureMail()
        );
        $this->assertSame(
            $formData['option_ip_address_lock'],
            $Config->getOptionIpAddressLock()
        );
        $this->assertSame(
            $formData['option_ip_address_lock_count'],
            $Config->getOptionIpAddressLockCount()
        );
    }

    /**
     * すべての項目を有効にした状態での更新チェック
     */
    public function testUpdate()
    {
        // フォームデータをPOST
        $formData  = [
            '_token'                       => 'dummy',
            'option_tfa'                   => true,
            'option_tfa_master_key'        => bin2hex(openssl_random_pseudo_bytes(16)),
            'option_login_success_mail'    => true,
            'option_login_failure_mail'    => true,
            'option_ip_address_lock'       => true,
            'option_ip_address_lock_count' => 5,
        ];
        $this->client->request(
            'POST',
            $this->generateUrl('jsys_asi_admin_config'),
            ['config' => $formData]
        );

        // 設定画面にリダイレクトされたか
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl(
            'jsys_asi_admin_config'
        )));
        $crawler = $this->client->followRedirect();

        // 登録済みメッセージが含まれているか
        $this->assertContains('登録しました。', $crawler->html());

        // 作成時のマスターキーの値が含まれているか
        $masterKey = $formData['option_tfa_master_key'];
        $this->assertContains($masterKey, $crawler->html());

        // DBからレコードを取得
        $Config      = $this->configRepo->get();
        $dbMasterKey = $Config->getOptionTfaMasterKey();
        $password    = $Config->getOptionTfaMasterKeyPassword();
        $salt        = $Config->getOptionTfaMasterKeySalt();

        // マスターキー：空でないか
        $this->assertNotEmpty($dbMasterKey);
        // マスターキー：暗号化前の値と変わっているか
        $this->assertNotSame($masterKey, $dbMasterKey);
        // マスターキー：DBを復号化した値とフォームの値が同じか
        $decrypted = $this->service->decrypt($dbMasterKey, $password, $salt);
        $this->assertSame($masterKey, $decrypted);

        // その他：フォームデータと同じ値か
        $this->assertSame(
            $formData['option_login_success_mail'],
            $Config->getOptionLoginSuccessMail()
        );
        $this->assertSame(
            $formData['option_login_failure_mail'],
            $Config->getOptionLoginFailureMail()
        );
        $this->assertSame(
            $formData['option_ip_address_lock'],
            $Config->getOptionIpAddressLock()
        );
        $this->assertSame(
            $formData['option_ip_address_lock_count'],
            $Config->getOptionIpAddressLockCount()
        );
    }

}
