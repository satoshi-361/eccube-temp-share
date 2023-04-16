<?php
namespace Plugin\JsysAsi\Tests\Service;

use Faker\Generator;
use Eccube\Tests\Service\AbstractServiceTestCase;
use Plugin\JsysAsi\Service\JsysAsiCryptService;

class JsysAsiCryptServiceTest extends AbstractServiceTestCase
{
    /**
     * @var Generator
     */
    protected $faker;

    /**
     * @var JsysAsiCryptService
     */
    protected $service;


    /**
     * Setup method.
     * {@inheritDoc}
     * @see \Eccube\Tests\EccubeTestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();

        $this->faker   = $this->getFaker();
        $this->service = self::$container->get(JsysAsiCryptService::class);
    }

    /**
     * 暗号化時、データが空で例外
     */
    public function testEncryptDataError()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('データが設定されていません。');

        $data     = null;
        $password = $this->service->createPassword();
        $salt     = $this->service->createSalt();
        $this->service->encrypt($data, $password, $salt);
    }

    /**
     * 暗号化時、パスワードが空で例外
     */
    public function testEncryptPasswordError()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('パスワードが設定されていません。');

        $data     = $this->faker->word;
        $password = null;
        $salt     = $this->service->createSalt();
        $this->service->encrypt($data, $password, $salt);
    }

    /**
     * 暗号化時、ソルトが空で例外
     */
    public function testEncryptSaltError()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('ソルトが設定されていません。');

        $data     = $this->faker->word;
        $password = $this->service->createPassword();
        $salt     = null;
        $this->service->encrypt($data, $password, $salt);
    }

    /**
     * 復号化時、データが空で例外
     */
    public function testDecryptDataError()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('データが設定されていません。');

        $data     = null;
        $password = $this->service->createPassword();
        $salt     = $this->service->createSalt();
        $this->service->decrypt($data, $password, $salt);
    }

    /**
     * 復号化時、パスワードが空で例外
     */
    public function testDecryptPasswordError()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('パスワードが設定されていません。');

        $data     = $this->faker->word;
        $password = null;
        $salt     = $this->service->createSalt();
        $this->service->decrypt($data, $password, $salt);
    }

    /**
     * 復号化時、ソルトが空で例外
     */
    public function testDecryptSaltError()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('ソルトが設定されていません。');

        $data     = $this->faker->word;
        $password = $this->service->createPassword();
        $salt     = null;
        $this->service->decrypt($data, $password, $salt);
    }

    /**
     * 暗号・復号化テスト
     */
    public function testCrypt()
    {
        $data = $this->faker->word;

        // パスワード 32桁の文字列か
        $password = $this->service->createPassword();
        $this->assertIsString($password);
        $this->assertSame(32, strlen($password));

        // ソルト 32桁の文字列か
        $salt = $this->service->createSalt();
        $this->assertIsString($salt);
        $this->assertSame(32, strlen($salt));

        // 暗号化 空でないか、作成したデータと違うか
        $encrypted = $this->service->encrypt($data, $password, $salt);
        $this->assertGreaterThan(0, strlen($encrypted));
        $this->assertNotSame($data, $encrypted);

        // 復号化 空で無いか、作成したデータと同じか
        $decrypted = $this->service->decrypt($encrypted, $password, $salt);
        $this->assertGreaterThan(0, strlen($decrypted));
        $this->assertSame($data, $decrypted);
    }

}
