<?php
namespace Plugin\JsysAsi\Tests\Web\Admin;

use OTPHP\TOTP;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Member;
use Eccube\Tests\Web\AbstractWebTestCase;
use Plugin\JsysAsi\Entity\Config;
use Plugin\JsysAsi\Entity\JsysAsiTfaUser;
use Plugin\JsysAsi\Service\JsysAsiCryptService;
use Plugin\JsysAsi\Service\JsysAsiTfaService;

class LoginControllerFailureMailTest extends AbstractWebTestCase
{
    /**
     * @var \Swift_Plugins_MessageLogger
     */
    protected $logger;

    /**
     * @var JsysAsiCryptService
     */
    protected $cryptService;

    /**
     * @var JsysAsiTfaService
     */
    protected $tfaService;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;


    /**
     * Setup method.
     * {@inheritDoc}
     * @see \Eccube\Tests\Web\AbstractWebTestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();

        $this->logger = self::$container->get(
            'swiftmailer.mailer.default.plugin.messagelogger'
        );
        $this->cryptService = self::$container->get(JsysAsiCryptService::class);
        $this->tfaService   = self::$container->get(JsysAsiTfaService::class);
        $this->BaseInfo     = $this->entityManager->find(BaseInfo::class, 1);
    }

    /**
     * 通常ログイン成功未送信テスト
     */
    public function testLoginSuccess()
    {
        // ログイン失敗通知を有効にしてプラグイン設定作成
        $this->createConfig();
        // メンバーを作成
        $Member = $this->createMember();

        // ログイン、成功するか
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'    => $Member->getLoginId(),
            'password'    => 'password',
            '_csrf_token' => 'dummy',
        ]);
        $this->assertNotNull(
            self::$container->get('security.token_storage')->getToken()
        );

        // メールは取得できないか
        $Messages = $this->getMessages();
        $this->assertEmpty($Messages);
    }

    /**
     * 2要素ユーザーログイン成功未送信テスト
     */
    public function testTfaUserLoginSuccess()
    {
        // ログイン失敗通知・2要素認証を有効にしてプラグイン設定作成
        $this->createConfig(true, true);
        // メンバー・2要素認証ユーザーを作成
        $Member  = $this->createMember();
        $tfaUser = $this->createTfaUser($Member);

        // 正しい認証コードでログイン、成功するか
        $code = $this->generateTotp($tfaUser);
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'     => $Member->getLoginId(),
            'password'     => 'password',
            'jsys_asi_otp' => $code,
            '_csrf_token'  => 'dummy',
        ]);
        $this->assertNotNull(
            self::$container->get('security.token_storage')->getToken()
        );

        // メールは取得できないか
        $Messages = $this->getMessages();
        $this->assertEmpty($Messages);
    }

    /**
     * 非2要素ユーザーログイン成功未送信テスト
     */
    public function testNotTfaUserLoginSuccess()
    {
        // ログイン失敗通知・2要素認証を有効にしてプラグイン設定作成
        $this->createConfig(true, true);
        // メンバーを作成
        $Member = $this->createMember();

        // 認証コードをnullでログイン、成功するか
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'     => $Member->getLoginId(),
            'password'     => 'password',
            'jsys_asi_otp' => null,
            '_csrf_token'  => 'dummy',
        ]);
        $this->assertNotNull(
            self::$container->get('security.token_storage')->getToken()
        );

        // メールは取得できないか
        $Messages = $this->getMessages();
        $this->assertEmpty($Messages);
    }

    /**
     * 通知無効通常ログイン失敗未送信テスト
     */
    public function testDisabled()
    {
        // ログイン失敗通知を無効にしてプラグイン設定作成
        $this->createConfig(false);
        // メンバーを作成
        $Member = $this->createMember();

        // 違うパスワードでログイン、失敗するか
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'    => $Member->getLoginId(),
            'password'    => 'pass',
            '_csrf_token' => 'dummy',
        ]);
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());

        // メールを取得できないか
        $Messages = $this->getMessages();
        $this->assertEmpty($Messages);
    }

    /**
     * 通常ログイン失敗送信テスト
     */
    public function testLoginFailure()
    {
        $baseInfo = $this->BaseInfo;

        // ログイン失敗通知を有効にしてプラグイン設定作成
        $this->createConfig();
        // メンバーを作成
        $Member = $this->createMember();
        // 違うパスワードでログイン、失敗するか
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'    => $Member->getLoginId(),
            'password'    => 'pass',
            '_csrf_token' => 'dummy',
        ]);
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());

        // メールを取得、1件送信されているか
        $Messages = $this->getMessages();
        $this->assertCount(1, $Messages);

        // メールの内容を確認
        /** @var \Swift_Mime_SimpleMessage $Message */
        $Message = current($Messages);
        $chk     = $this->getArrayForMailCheck($Member);
        $this->assertSame($chk['subject'], $Message->getSubject());
        $this->assertArrayHasKey($baseInfo->getEmail01(), $Message->getFrom());
        $this->assertArrayHasKey($baseInfo->getEmail01(), $Message->getTo());
        $this->assertArrayHasKey($baseInfo->getEmail03(), $Message->getReplyTo());
        $this->assertSame($baseInfo->getEmail04(), $Message->getReturnPath());
        $this->assertContains($chk['header'], $Message->getBody());
        $this->assertContains($chk['shop'], $Message->getBody());
        $this->assertContains($chk['id'], $Message->getBody());
        $this->assertContains($chk['ip'], $Message->getBody());
        $this->assertContains($chk['ua'], $Message->getBody());
    }

    /**
     * 不正ログイン送信テスト
     */
    public function testIllegalLogin()
    {
        $baseInfo = $this->BaseInfo;

        // ログイン失敗通知・2要素認証を有効にしてプラグイン設定作成
        $this->createConfig(true, true);
        // メンバー・2要素認証ユーザーを作成
        $Member = $this->createMember();
        $this->createTfaUser($Member);

        // 違う認証コードでログイン、失敗するか
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'     => $Member->getLoginId(),
            'password'     => 'password',
            'jsys_asi_otp' => '1234567',
            '_csrf_token'  => 'dummy',
        ]);
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());

        // メールを取得、1件送信されているか
        $Messages = $this->getMessages();
        $this->assertCount(1, $Messages);

        // メールの内容を確認
        /** @var \Swift_Mime_SimpleMessage $Message */
        $Message = current($Messages);
        $chk     = $this->getArrayForMailCheck($Member);
        $this->assertSame($chk['subject'], $Message->getSubject());
        $this->assertArrayHasKey($baseInfo->getEmail01(), $Message->getFrom());
        $this->assertArrayHasKey($baseInfo->getEmail01(), $Message->getTo());
        $this->assertArrayHasKey($baseInfo->getEmail03(), $Message->getReplyTo());
        $this->assertSame($baseInfo->getEmail04(), $Message->getReturnPath());
        $this->assertContains($chk['header'], $Message->getBody());
        $this->assertContains($chk['shop'], $Message->getBody());
        $this->assertContains($chk['id'], $Message->getBody());
        $this->assertContains($chk['ip'], $Message->getBody());
        $this->assertContains($chk['ua'], $Message->getBody());
    }


    /**
     * プラグイン設定を作成します。
     * @param boolean $enableFailure
     * @param boolean $enableTfa
     * @return \Plugin\JsysAsi\Entity\Config|object|NULL
     */
    private function createConfig($enableFailure = true, $enableTfa = false)
    {
        $Config = $this->entityManager->find(Config::class, 1);
        if (!$Config) {
            $Config = new Config();
        }

        $Config
            ->setOptionTfa(false)
            ->setOptionTfaMasterKey(null)
            ->setOptionTfaMasterKeyPassword(null)
            ->setOptionTfaMasterKeySalt(null)
            ->setOptionLoginSuccessMail(false)
            ->setOptionLoginFailureMail($enableFailure)
            ->setOptionIpAddressLock(false)
            ->setOptionIpAddressLockCount(null);

        // 2要素認証有効にする場合
        if ($enableTfa) {
            $masterKey = bin2hex(openssl_random_pseudo_bytes(16));
            $password  = $this->cryptService->createPassword();
            $salt      = $this->cryptService->createSalt();
            $encrypted = $this->cryptService->encrypt($masterKey, $password, $salt);
            $Config
                ->setOptionTfa(true)
                ->setOptionTfaMasterKey($encrypted)
                ->setOptionTfaMasterKeyPassword($password)
                ->setOptionTfaMasterKeySalt($salt);
        }

        $this->entityManager->persist($Config);
        $this->entityManager->flush($Config);

        return $Config;
    }

    /**
     * メッセージロガーからメッセージを取得します。
     * @return \Swift_Mime_SimpleMessage[]
     */
    private function getMessages()
    {
        $messages = [];
        foreach ($this->logger->getMessages() as $Message) {
            $messages[$Message->getId()] = $Message;
        }
        return $messages;
    }

    /**
     * メンバーと2要素認証ユーザーを作成します。
     * @param Member $Member
     * @return \Plugin\JsysAsi\Entity\JsysAsiTfaUser
     */
    private function createTfaUser(Member $Member)
    {
        $secret    = $this->tfaService->generateSecret();
        $password  = $this->cryptService->createPassword();
        $salt      = $this->cryptService->createSalt();
        $encrypted = $this->cryptService->encrypt($secret, $password, $salt);

        $tfaUser   = new JsysAsiTfaUser();
        $tfaUser
            ->setMemberId($Member->getId())
            ->setEnabled(true)
            ->setSecret($encrypted)
            ->setSecretPassword($password)
            ->setSecretSalt($salt);

        $this->entityManager->persist($tfaUser);
        $this->entityManager->flush($tfaUser);

        return $tfaUser;
    }

    /**
     * TOTPを作成します。
     * @param JsysAsiTfaUser $tfaUser
     * @return string
     */
    private function generateTotp(JsysAsiTfaUser $tfaUser)
    {
        $secret = $this->cryptService->decrypt(
            $tfaUser->getSecret(),
            $tfaUser->getSecretPassword(),
            $tfaUser->getSecretSalt()
        );
        $totp = TOTP::create($secret);
        return $totp->at(time());
    }

    /**
     * メールチェック用の値配列を取得します。
     * @param Member $Member
     * @return string[]
     */
    private function getArrayForMailCheck(Member $Member)
    {
        $subject = trans('jsys_asi.mail.admin_login.failure.subject', [
            '%shop%' => $this->BaseInfo->getShopName()
        ]);
        $header  = trans('jsys_asi.mail.admin_login.failure.header');
        $shop    = trans('jsys_asi.mail.admin_login.failure.shop.title')
                 . "：{$this->BaseInfo->getShopName()}";
        $id      = trans('jsys_asi.mail.admin_login.failure.login_id.title')
                 . "：{$Member->getLoginId()}";
        $ip      = trans('jsys_asi.mail.admin_login.failure.ip.title')
                 . "：{$this->client->getRequest()->getClientIp()}";
        $ua      = trans('jsys_asi.mail.admin_login.failure.ua.title')
                 . "：{$this->client->getRequest()->headers->get('user-agent')}";

        return [
            'subject' => $subject,
            'header'  => $header,
            'shop'    => $shop,
            'id'      => $id,
            'ip'      => $ip,
            'ua'      => $ua,
        ];
    }

}
