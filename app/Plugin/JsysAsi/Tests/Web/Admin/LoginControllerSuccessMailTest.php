<?php
namespace Plugin\JsysAsi\Tests\Web\Admin;

use OTPHP\TOTP;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Member;
use Eccube\Tests\Web\AbstractWebTestCase;
use Plugin\JsysAsi\Entity\Config;
use Plugin\JsysAsi\Entity\JsysAsiTfaUser;
use Plugin\JsysAsi\Repository\ConfigRepository;
use Plugin\JsysAsi\Service\JsysAsiCryptService;
use Plugin\JsysAsi\Service\JsysAsiTfaService;

class LoginControllerSuccessMailTest extends AbstractWebTestCase
{
    /**
     * @var \DateTimeZone
     */
    protected $timezone;

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
     * @var ConfigRepository
     */
    protected $configRepo;

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

        $this->timezone = new \DateTimeZone(self::$container->getParameter(
            'timezone'
        ));
        $this->logger = self::$container->get(
            'swiftmailer.mailer.default.plugin.messagelogger'
        );
        $this->cryptService = self::$container->get(JsysAsiCryptService::class);
        $this->tfaService   = self::$container->get(JsysAsiTfaService::class);
        $this->configRepo   = self::$container->get(ConfigRepository::class);
        $this->BaseInfo     = $this->entityManager->find(BaseInfo::class, 1);
    }

    /**
     * ログイン失敗時未送信テスト
     */
    public function testLoginFailure()
    {
        // ログイン成功通知を有効にしてプラグイン設定作成
        $this->createConfig();
        // メンバーを作成
        $Member = $this->createMember();

        // ログインを失敗させる
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'    => $Member->getLoginId(),
            'password'    => 'pass',
            '_csrf_token' => 'dummy',
        ]);
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());

        // メールは取得できないか
        $Messages = $this->getMessages();
        $this->assertEmpty($Messages);
    }

    /**
     * 不正ログイン時未送信テスト
     */
    public function testIllegalLogin()
    {
        // 2要素認証とログイン成功通知を有効にしてプラグイン設定作成
        $this->createConfig(true, true);
        // メンバーと2要素認証ユーザーを作成
        $Member = $this->createMember();
        $this->createTfaUser($Member);

        // 間違った認証コードでログイン、失敗するか
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'     => $Member->getLoginId(),
            'password'     => 'password',
            'jsys_asi_otp' => '1234567',
            '_csrf_token'  => 'dummy',
        ]);
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());

        // メールは取得できないか
        $Messages = $this->getMessages();
        $this->assertEmpty($Messages);
    }

    /**
     * ログイン成功通知無効未送信テスト
     */
    public function testDisabled()
    {
        // ログイン成功通知を無効にしてプラグイン設定作成
        $this->createConfig(false);
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
     * 通常ログイン時送信テスト
     */
    public function testLoginSuccess()
    {
        $baseInfo = $this->BaseInfo;

        // ログイン成功通知を有効にしてプラグイン設定作成
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

        // 送信されたメールを取得
        $Messages = $this->getMessages();
        // メール件数は1件か
        $this->assertCount(1, $Messages);

        // メールチェック用文字列を取得
        $chk = $this->getArrayForMailCheck($Member);
        // メールの内容を確認
        /** @var \Swift_Mime_SimpleMessage $Message */
        $Message = current($Messages);
        $this->assertSame($chk['subject'], $Message->getSubject());
        $this->assertArrayHasKey($baseInfo->getEmail01(), $Message->getFrom());
        $this->assertArrayHasKey($baseInfo->getEmail01(), $Message->getTo());
        $this->assertArrayHasKey($baseInfo->getEmail03(), $Message->getReplyTo());
        $this->assertSame($baseInfo->getEmail04(), $Message->getReturnPath());
        $this->assertContains($chk['header'], $Message->getBody());
        $this->assertContains($chk['shop'], $Message->getBody());
        $this->assertContains($chk['id'], $Message->getBody());
        $this->assertContains($chk['name'], $Message->getBody());
        $this->assertContains($chk['date'], $Message->getBody());
        $this->assertContains($chk['ip'], $Message->getBody());
        $this->assertContains($chk['ua'], $Message->getBody());
    }

    /**
     * 2要素認証ユーザーログイン時送信テスト
     */
    public function testTfaUserLoginSuccess()
    {
        $baseInfo = $this->BaseInfo;

        // 2要素認証とログイン成功通知を有効にしてプラグイン設定作成
        $this->createConfig(true, true);
        // メンバーと2要素認証ユーザーを作成
        $Member  = $this->createMember();
        $tfaUser = $this->createTfaUser($Member);

        // 認証コードを生成
        $code = $this->generateTotp($tfaUser);
        // ログイン、成功するか
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'     => $Member->getLoginId(),
            'password'     => 'password',
            'jsys_asi_otp' => $code,
            '_csrf_token'  => 'dummy',
        ]);
        $this->assertNotNull(
            self::$container->get('security.token_storage')->getToken()
        );

        // 送信されたメールを取得
        $Messages = $this->getMessages();
        // メール件数は1件か
        $this->assertCount(1, $Messages);

        // メールチェック用文字列を取得
        $chk = $this->getArrayForMailCheck($Member);
        // メールの内容を確認
        /** @var \Swift_Mime_SimpleMessage $Message */
        $Message = current($Messages);
        $this->assertSame($chk['subject'], $Message->getSubject());
        $this->assertArrayHasKey($baseInfo->getEmail01(), $Message->getFrom());
        $this->assertArrayHasKey($baseInfo->getEmail01(), $Message->getTo());
        $this->assertArrayHasKey($baseInfo->getEmail03(), $Message->getReplyTo());
        $this->assertSame($baseInfo->getEmail04(), $Message->getReturnPath());
        $this->assertContains($chk['header'], $Message->getBody());
        $this->assertContains($chk['shop'], $Message->getBody());
        $this->assertContains($chk['id'], $Message->getBody());
        $this->assertContains($chk['name'], $Message->getBody());
        $this->assertContains($chk['date'], $Message->getBody());
        $this->assertContains($chk['ip'], $Message->getBody());
        $this->assertContains($chk['ua'], $Message->getBody());
    }

    /**
     * 非2要素認証ユーザーログイン時送信テスト
     */
    public function testNotTfaUserLoginSuccess()
    {
        $baseInfo = $this->BaseInfo;

        // 2要素認証とログイン成功通知を有効にしてプラグイン設定作成
        $this->createConfig(true, true);
        // メンバーと2要素認証ユーザーを作成
        $Member = $this->createMember();

        // ログイン、成功するか
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'     => $Member->getLoginId(),
            'password'     => 'password',
            'jsys_asi_otp' => null,
            '_csrf_token'  => 'dummy',
        ]);
        $this->assertNotNull(
            self::$container->get('security.token_storage')->getToken()
        );

        // 送信されたメールを取得
        $Messages = $this->getMessages();
        // メール件数は1件か
        $this->assertCount(1, $Messages);

        // メールチェック用文字列を取得
        $chk = $this->getArrayForMailCheck($Member);
        // メールの内容を確認
        /** @var \Swift_Mime_SimpleMessage $Message */
        $Message = current($Messages);
        $this->assertSame($chk['subject'], $Message->getSubject());
        $this->assertArrayHasKey($baseInfo->getEmail01(), $Message->getFrom());
        $this->assertArrayHasKey($baseInfo->getEmail01(), $Message->getTo());
        $this->assertArrayHasKey($baseInfo->getEmail03(), $Message->getReplyTo());
        $this->assertSame($baseInfo->getEmail04(), $Message->getReturnPath());
        $this->assertContains($chk['header'], $Message->getBody());
        $this->assertContains($chk['shop'], $Message->getBody());
        $this->assertContains($chk['id'], $Message->getBody());
        $this->assertContains($chk['name'], $Message->getBody());
        $this->assertContains($chk['date'], $Message->getBody());
        $this->assertContains($chk['ip'], $Message->getBody());
        $this->assertContains($chk['ua'], $Message->getBody());
    }


    /**
     * プラグイン設定を作成します。
     * @param boolean $enableSuccess
     * @param boolean $enabledTfa
     * @return \Plugin\JsysAsi\Entity\Config
     */
    private function createConfig($enableSuccess = true, $enabledTfa = false)
    {
        $Config = $this->configRepo->get();
        if (!$Config) {
            $Config = new Config();
        }

        $Config
            ->setOptionTfa(false)
            ->setOptionTfaMasterKey(null)
            ->setOptionTfaMasterKeyPassword(null)
            ->setOptionTfaMasterKeySalt(null)
            ->setOptionLoginSuccessMail($enableSuccess)
            ->setOptionLoginFailureMail(false)
            ->setOptionIpAddressLock(false)
            ->setOptionIpAddressLockCount(null);

        // 2要素認証有効にする場合
        if ($enabledTfa) {
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
     * メールチェック用の値配列を取得します。
     * @param Member $Member
     * @return string[]
     */
    private function getArrayForMailCheck(Member $Member)
    {
        $loginDate = \IntlDateFormatter::formatObject(
            $Member->getLoginDate()->setTimezone($this->timezone),
            \IntlDateFormatter::MEDIUM
        );

        $subject = trans('jsys_asi.mail.admin_login.success.subject', [
            '%shop%'   => $this->BaseInfo->getShopName(),
            '%member%' => $Member->getName(),
        ]);
        $header  = trans('jsys_asi.mail.admin_login.success.header');
        $shop    = trans('jsys_asi.mail.admin_login.success.shop.title')
                 . "：{$this->BaseInfo->getShopName()}";
        $id      = trans('jsys_asi.mail.admin_login.success.login_id.title')
                 . "：{$Member->getLoginId()}";
        $name    = trans('jsys_asi.mail.admin_login.success.member.title')
                 . "：{$Member->getName()}";
        $date    = trans('jsys_asi.mail.admin_login.success.login_date.title')
                 . "：{$loginDate}";
        $ip      = trans('jsys_asi.mail.admin_login.success.ip.title')
                 . "：{$this->client->getRequest()->getClientIp()}";
        $ua      = trans('jsys_asi.mail.admin_login.success.ua.title')
                 . "：{$this->client->getRequest()->headers->get('user-agent')}";

        return [
            'subject' => $subject,
            'header'  => $header,
            'shop'    => $shop,
            'id'      => $id,
            'name'    => $name,
            'date'    => $date,
            'ip'      => $ip,
            'ua'      => $ua,
        ];
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
     * OTPを生成します。
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

}
