<?php
namespace Plugin\JsysAsi\Tests\Web\Admin;

use OTPHP\TOTP;
use Eccube\Tests\Web\AbstractWebTestCase;
use Plugin\JsysAsi\Entity\Config;
use Plugin\JsysAsi\Entity\JsysAsiTfaOtpHistory;
use Plugin\JsysAsi\Entity\JsysAsiTfaUser;
use Plugin\JsysAsi\Repository\ConfigRepository;
use Plugin\JsysAsi\Repository\JsysAsiTfaOtpHistoryRepository;
use Plugin\JsysAsi\Service\JsysAsiCryptService;
use Plugin\JsysAsi\Service\JsysAsiTfaService;
use Eccube\Entity\Member;

class LoginControllerTfaTest extends AbstractWebTestCase
{
    /**
     * @var ConfigRepository
     */
    protected $configRepo;

    /**
     * @var JsysAsiTfaOtpHistoryRepository
     */
    protected $historyRepo;

    /**
     * @var JsysAsiCryptService
     */
    protected $cryptService;

    /**
     * @var JsysAsiTfaService
     */
    protected $tfaService;


    /**
     * Setup method.
     * {@inheritDoc}
     * @see \Eccube\Tests\Web\AbstractWebTestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();

        $this->configRepo   = self::$container->get(ConfigRepository::class);
        $this->historyRepo  = self::$container->get(JsysAsiTfaOtpHistoryRepository::class);
        $this->cryptService = self::$container->get(JsysAsiCryptService::class);
        $this->tfaService   = self::$container->get(JsysAsiTfaService::class);

        $this->deleteAllRows(['plg_jsys_asi_tfa_otp_history']);
    }

    /**
     * 2要素ユーザー未登録認証コードありテスト
     */
    public function testAuthNotTfaUserInputCode()
    {
        // プラグイン設定・メンバーを作成
        $this->createConfig();
        $Member = $this->createMember();

        // 認証コードを入れでログイン、失敗するか
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'     => $Member->getLoginId(),
            'password'     => 'password',
            'jsys_asi_otp' => '1234567',
            '_csrf_token'  => 'dummy',
        ]);
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());

        $crawler = $this->client->followRedirect();
        $this->assertContains('ログインできませんでした。', $crawler->html());
    }

    /**
     * 2要素ユーザー未登録認証コードnullテスト
     */
    public function testAuthNotTfaUserCodeNull()
    {
        // プラグイン設定・メンバーを作成
        $this->createConfig();
        $Member = $this->createMember();

        // 認証コードをnullにしてログイン、成功するか
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'     => $Member->getLoginId(),
            'password'     => 'password',
            'jsys_asi_otp' => null,
            '_csrf_token'  => 'dummy',
        ]);
        $this->assertNotNull(
            self::$container->get('security.token_storage')->getToken()
        );

        $crawler = $this->client->followRedirect();
        $this->assertContains(
            "{$Member->getName()} 様",
            $crawler->filter('.c-headerBar__userMenu')->html()
        );
        $this->assertContains(
            'ホーム',
            $crawler->filter('.c-mainNavArea__navItemTitle')->html()
        );
    }

    /**
     * 無効2要素ユーザー認証コードありテスト
     */
    public function testAuthDisabledTfaUserInputCode()
    {
        // プラグイン設定・メンバー・無効な2要素認証ユーザーを作成
        $Member = $this->createMember();
        $this->createConfig();
        $this->createTfaUser($Member, false);

        // 認証コードを入れてログイン、失敗するか
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'     => $Member->getLoginId(),
            'password'     => 'password',
            'jsys_asi_otp' => '1234567',
            '_csrf_token'  => 'dummy',
        ]);
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());

        $crawler = $this->client->followRedirect();
        $this->assertContains('ログインできませんでした。', $crawler->html());
    }

    /**
     * 無効2要素ユーザー認証コードnullテスト
     */
    public function testAuthDisabledTfaUserCodeNull()
    {
        // プラグイン設定・メンバー・無効な2要素認証ユーザーを作成
        $Member = $this->createMember();
        $this->createConfig();
        $this->createTfaUser($Member, false);

        // 認証コードをnullにしてログイン、成功するか
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'     => $Member->getLoginId(),
            'password'     => 'password',
            'jsys_asi_otp' => null,
            '_csrf_token'  => 'dummy',
        ]);
        $this->assertNotNull(
            self::$container->get('security.token_storage')->getToken()
        );

        $crawler = $this->client->followRedirect();
        $this->assertContains(
            "{$Member->getName()} 様",
            $crawler->filter('.c-headerBar__userMenu')->html()
        );
        $this->assertContains(
            'ホーム',
            $crawler->filter('.c-mainNavArea__navItemTitle')->html()
        );
    }

    /**
     * 2要素認証コードnullテスト
     */
    public function testAuthTfaCodeNull()
    {
        // プラグイン設定・メンバー・2要素認証ユーザーを作成
        $Member = $this->createMember();
        $this->createConfig();
        $this->createTfaUser($Member);

        // 認証コードをnullにしてログイン、失敗するか
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'     => $Member->getLoginId(),
            'password'     => 'password',
            'jsys_asi_otp' => null,
            '_csrf_token'  => 'dummy',
        ]);
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());

        $crawler = $this->client->followRedirect();
        $this->assertContains('ログインできませんでした。', $crawler->html());
    }

    /**
     * 2要素認証コード違いテスト
     */
    public function testAuthTfaInvalidCode()
    {
        // プラグイン設定・メンバー・2要素認証ユーザーを作成
        $Member = $this->createMember();
        $this->createConfig();
        $this->createTfaUser($Member);

        // 間違った認証コードでログイン、失敗するか
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'     => $Member->getLoginId(),
            'password'     => 'password',
            'jsys_asi_otp' => '1234567',
            '_csrf_token'  => 'dummy',
        ]);
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());

        $crawler = $this->client->followRedirect();
        $this->assertContains('ログインできませんでした。', $crawler->html());
    }

    /**
     * 2要素認証テスト
     */
    public function testAuthTfa()
    {
        // プラグイン設定・メンバー・2要素認証ユーザーを作成
        $this->createConfig();
        $Member  = $this->createMember();
        $tfaUser = $this->createTfaUser($Member);

        // 正しい認証コードを生成
        $code = $this->generateTotp($tfaUser);

        // 正解の認証コードでログイン、成功するか
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'     => $Member->getLoginId(),
            'password'     => 'password',
            'jsys_asi_otp' => $code,
            '_csrf_token'  => 'dummy',
        ]);
        $this->assertNotNull(
            self::$container->get('security.token_storage')->getToken()
        );

        $crawler = $this->client->followRedirect();
        $this->assertContains(
            "{$Member->getName()} 様",
            $crawler->filter('.c-headerBar__userMenu')->html()
        );
        $this->assertContains(
            'ホーム',
            $crawler->filter('.c-mainNavArea__navItemTitle')->html()
        );

        // 履歴に認証コードが登録されているか
        $history = $this->historyRepo->findOneBy([
            'tfa_user_id' => $tfaUser->getId(),
            'otp'         => $code,
        ]);
        $this->assertNotNull($history);
    }

    /**
     * 2要素認証使用済みコードテスト
     */
    public function testAuthTfaUsedCode()
    {
        // プラグイン設定・メンバー・2要素認証ユーザーを作成
        $this->createConfig();
        $Member  = $this->createMember();
        $tfaUser = $this->createTfaUser($Member);

        // 正しい認証コードを生成して履歴に保存
        $code = $this->generateTotp($tfaUser);
        $this->insertOtpHistory($tfaUser, $code);

        // 正解の認証コードでログイン、失敗するか
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'     => $Member->getLoginId(),
            'password'     => 'password',
            'jsys_asi_otp' => $code,
            '_csrf_token'  => 'dummy',
        ]);
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());

        $crawler = $this->client->followRedirect();
        $this->assertContains('ログインできませんでした。', $crawler->html());
    }

    /**
     * 2要素認証過去コードテスト
     */
    public function testAuthTfaPastCode()
    {
        // プラグイン設定・メンバー・2要素認証ユーザーを作成
        $this->createConfig();
        $Member  = $this->createMember();
        $tfaUser = $this->createTfaUser($Member);

        // 正しい認証コードを生成
        $code = $this->generateTotp($tfaUser);

        // OTP履歴へ1時間前で登録
        $date = new \DateTime();
        $date->modify('-1 hour');
        $this->insertOtpHistory($tfaUser, $code, $date);
        // OTP履歴へ正解と適当な認証コードを5日前で登録
        $date = new \DateTime();
        $date->modify('-5 day');
        $this->insertOtpHistory($tfaUser, $code, $date);
        $this->insertOtpHistory($tfaUser, '000000', $date);

        // OTP履歴から2日以上前のレコードが2件なのを確認
        $dateTwoDaysAgo = new \DateTime();
        $dateTwoDaysAgo->modify('-2 day');
        $histories = $this->historyRepo->createQueryBuilder('h')
            ->select()
            ->where('h.create_date < :create_date')
            ->setParameter('create_date', $dateTwoDaysAgo)
            ->getQuery()
            ->getResult();
        $this->assertCount(2, $histories);

        // 正解の認証コードでログイン、成功するか
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'     => $Member->getLoginId(),
            'password'     => 'password',
            'jsys_asi_otp' => $code,
            '_csrf_token'  => 'dummy',
        ]);
        $this->assertNotNull(
            self::$container->get('security.token_storage')->getToken()
        );

        $crawler = $this->client->followRedirect();
        $this->assertContains(
            "{$Member->getName()} 様",
            $crawler->filter('.c-headerBar__userMenu')->html()
        );
        $this->assertContains(
            'ホーム',
            $crawler->filter('.c-mainNavArea__navItemTitle')->html()
        );

        // OTP履歴から2日以上前のレコードが削除されているのを確認
        $histories = $this->historyRepo->createQueryBuilder('h')
            ->select()
            ->where('h.create_date < :create_date')
            ->setParameter('create_date', $dateTwoDaysAgo)
            ->getQuery()
            ->getResult();
        $this->assertCount(0, $histories);
    }

    /**
     * マスターキー認証テスト
     */
    public function testAuthMasterKey()
    {
        // プラグイン設定・メンバー・2要素認証ユーザーを作成
        $Config  = $this->createConfig();
        $Member  = $this->createMember();
        $this->createTfaUser($Member);

        // プラグイン設定のマスターキーを復号化
        $code = $this->cryptService->decrypt(
            $Config->getOptionTfaMasterKey(),
            $Config->getOptionTfaMasterKeyPassword(),
            $Config->getOptionTfaMasterKeySalt()
        );

        // 認証コードをマスターキーにしてログイン、成功するか
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'     => $Member->getLoginId(),
            'password'     => 'password',
            'jsys_asi_otp' => $code,
            '_csrf_token'  => 'dummy',
        ]);
        $this->assertNotNull(
            self::$container->get('security.token_storage')->getToken()
        );

        $crawler = $this->client->followRedirect();
        $this->assertContains(
            "{$Member->getName()} 様",
            $crawler->filter('.c-headerBar__userMenu')->html()
        );
        $this->assertContains(
            'ホーム',
            $crawler->filter('.c-mainNavArea__navItemTitle')->html()
        );
    }

    /**
     * スーパーバイザー認証無効値コードnullテスト
     */
//     public function testAuthSupervisorWithInvalidValueAndCodeNull()
//     {
//         /*
//          * services.yaml
//          * parametersへjsys_asi_tfa_supervisor: falseを追加しておく
//          */

//         // プラグイン設定・メンバー・2要素認証ユーザーを作成
//         $Member = $this->createMember();
//         $this->createConfig();
//         $this->createTfaUser($Member);

//         // 認証コードをnullにしてログイン、失敗するか
//         $this->client->request('POST', $this->generateUrl('admin_login'), [
//             'login_id'     => $Member->getLoginId(),
//             'password'     => 'password',
//             'jsys_asi_otp' => null,
//             '_csrf_token'  => 'dummy',
//         ]);
//         $this->assertSame(302, $this->client->getResponse()->getStatusCode());

//         $crawler = $this->client->followRedirect();
//         $this->assertContains('ログインできませんでした。', $crawler->html());
//     }

    /**
     * スーパーバイザー認証無効値コード違いテスト
     */
//     public function testAuthSupervisorWithInvalidValueAndInvalidCode()
//     {
//         /*
//          * services.yaml
//          * parametersへjsys_asi_tfa_supervisor: falseを追加しておく
//          */

//         // プラグイン設定・メンバー・2要素認証ユーザーを作成
//         $Member = $this->createMember();
//         $this->createConfig();
//         $this->createTfaUser($Member);

//         // 間違った認証コードでログイン、失敗するか
//         $this->client->request('POST', $this->generateUrl('admin_login'), [
//             'login_id'     => $Member->getLoginId(),
//             'password'     => 'password',
//             'jsys_asi_otp' => '1234567',
//             '_csrf_token'  => 'dummy',
//         ]);
//         $this->assertSame(302, $this->client->getResponse()->getStatusCode());

//         $crawler = $this->client->followRedirect();
//         $this->assertContains('ログインできませんでした。', $crawler->html());
//     }

    /**
     * スーパーバイザー認証有効値コードnullテスト
     */
//     public function testAuthSupervisorCodeNull()
//     {
//         /*
//          * services.yaml
//          * parametersへjsys_asi_tfa_supervisor: trueを追加しておく
//          */

//         // プラグイン設定・メンバー・2要素認証ユーザーを作成
//         $Member = $this->createMember();
//         $this->createConfig();
//         $this->createTfaUser($Member);

//         // 認証コードをnullにしてログイン、成功するか
//         $this->client->request('POST', $this->generateUrl('admin_login'), [
//             'login_id'     => $Member->getLoginId(),
//             'password'     => 'password',
//             'jsys_asi_otp' => null,
//             '_csrf_token'  => 'dummy',
//         ]);
//         $this->assertNotNull(
//             self::$container->get('security.token_storage')->getToken()
//         );

//         $crawler = $this->client->followRedirect();
//         $this->assertContains(
//             "{$Member->getName()} 様",
//             $crawler->filter('.c-headerBar__userMenu')->html()
//         );
//         $this->assertContains(
//             'ホーム',
//             $crawler->filter('.c-mainNavArea__navItemTitle')->html()
//         );
//     }

    /**
     * スーパーバイザー認証有効値コード違いテスト
     */
//     public function testAuthSupervisorInvalidCode()
//     {
//         /*
//          * services.yaml
//          * parametersへjsys_asi_tfa_supervisor: trueを追加しておく
//          */

//         // プラグイン設定・メンバー・2要素認証ユーザーを作成
//         $Member = $this->createMember();
//         $this->createConfig();
//         $this->createTfaUser($Member);

//         // 認証コードをnullにしてログイン、成功するか
//         $this->client->request('POST', $this->generateUrl('admin_login'), [
//             'login_id'     => $Member->getLoginId(),
//             'password'     => 'password',
//             'jsys_asi_otp' => '1234567',
//             '_csrf_token'  => 'dummy',
//         ]);
//         $this->assertNotNull(
//             self::$container->get('security.token_storage')->getToken()
//         );

//         $crawler = $this->client->followRedirect();
//         $this->assertContains(
//             "{$Member->getName()} 様",
//             $crawler->filter('.c-headerBar__userMenu')->html()
//         );
//         $this->assertContains(
//             'ホーム',
//             $crawler->filter('.c-mainNavArea__navItemTitle')->html()
//         );
//     }


    /**
     * プラグイン設定を作成します。
     * @return \Plugin\JsysAsi\Entity\Config|NULL
     */
    private function createConfig()
    {
        $Config = $this->configRepo->get();
        if (!$Config) {
            $Config = new Config();
            $Config->setCreateDate(new \DateTime());
        }

        $masterKey = bin2hex(openssl_random_pseudo_bytes(16));
        $password  = $this->cryptService->createPassword();
        $salt      = $this->cryptService->createSalt();
        $encrypted = $this->cryptService->encrypt($masterKey, $password, $salt);

        $Config
            ->setOptionTfa(true)
            ->setOptionTfaMasterKey($encrypted)
            ->setOptionTfaMasterKeyPassword($password)
            ->setOptionTfaMasterKeySalt($salt)
            ->setOptionLoginSuccessMail(false)
            ->setOptionLoginFailureMail(false)
            ->setOptionIpAddressLock(false)
            ->setOptionIpAddressLockCount(null)
            ->setUpdateDate(new \DateTime());

        $this->entityManager->persist($Config);
        $this->entityManager->flush($Config);

        return $Config;
    }

    /**
     * メンバーと2要素認証ユーザーを作成します。
     * @param Member $Member
     * @param boolean $enabled
     * @return \Plugin\JsysAsi\Entity\JsysAsiTfaUser
     */
    private function createTfaUser(Member $Member, $enabled = true)
    {
        $secret    = $this->tfaService->generateSecret();
        $password  = $this->cryptService->createPassword();
        $salt      = $this->cryptService->createSalt();
        $encrypted = $this->cryptService->encrypt($secret, $password, $salt);

        $tfaUser   = new JsysAsiTfaUser();
        $tfaUser
            ->setMemberId($Member->getId())
            ->setEnabled($enabled)
            ->setSecret($encrypted)
            ->setSecretPassword($password)
            ->setSecretSalt($salt)
            ->setCreateDate(new \DateTime())
            ->setUpdateDate(new \DateTime());

        $this->entityManager->persist($tfaUser);
        $this->entityManager->flush($tfaUser);

        return $tfaUser;
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

    /**
     * OTP履歴へ新規登録を行います。
     * @param JsysAsiTfaUser $tfaUser
     * @param string $otp
     * @param \DateTime $date
     */
    private function insertOtpHistory(JsysAsiTfaUser $tfaUser, $otp, $date = null)
    {
        $date       = empty($date) ? new \DateTime() : $date;
        $otpHistory = new JsysAsiTfaOtpHistory();
        $otpHistory
            ->setTfaUserId($tfaUser->getId())
            ->setOtp($otp);

        $this->entityManager->persist($otpHistory);
        $this->entityManager->flush($otpHistory);

        $otpHistory->setCreateDate($date);
        $this->entityManager->flush($otpHistory);
    }

}
