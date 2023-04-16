<?php
namespace Plugin\JsysAsi\Tests\Service;

use OTPHP\TOTP;
use Eccube\Tests\Service\AbstractServiceTestCase;
use Plugin\JsysAsi\Entity\Config;
use Plugin\JsysAsi\Entity\JsysAsiTfaUser;
use Plugin\JsysAsi\Entity\JsysAsiTfaOtpHistory;
use Plugin\JsysAsi\Repository\ConfigRepository;
use Plugin\JsysAsi\Repository\JsysAsiTfaOtpHistoryRepository;
use Plugin\JsysAsi\Repository\JsysAsiTfaUserRepository;
use Plugin\JsysAsi\Service\JsysAsiCryptService;
use Plugin\JsysAsi\Service\JsysAsiTfaService;
use Eccube\Entity\Member;

class JsysAsiTfaServiceTest extends AbstractServiceTestCase
{
    /**
     * @var ConfigRepository
     */
    protected $configRepo;

    /**
     * @var JsysAsiTfaUserRepository
     */
    protected $tfaUserRepo;

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
     * @see \Eccube\Tests\EccubeTestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();

        $this->tfaUserRepo  = self::$container->get(JsysAsiTfaUserRepository::class);
        $this->historyRepo  = self::$container->get(JsysAsiTfaOtpHistoryRepository::class);
        $this->cryptService = self::$container->get(JsysAsiCryptService::class);
        $this->tfaService   = self::$container->get(JsysAsiTfaService::class);
        $this->configRepo   = self::$container->get(ConfigRepository::class);

        $this->deleteAllRows(['plg_jsys_asi_tfa_otp_history']);
    }

    /**
     * 2要素認証ユーザー未登録テスト
     */
    public function testAuthNotTfaUser()
    {
        // プラグイン設定・メンバーを作成
        $this->createConfig();
        $Member = $this->createMember();

        // 間違った認証コードでチェック、失敗するか
        $result = $this->tfaService->isValidCode($Member, '1234567');
        $this->assertFalse($result);
        $this->assertSame(
            JsysAsiTfaService::RESULT_USER_NONE,
            $this->tfaService->getTfaResult()
        );

        // 認証コードをnullにしてチェック、成功するか
        $result = $this->tfaService->isValidCode($Member, null);
        $this->assertTrue($result);
        $this->assertSame(
            JsysAsiTfaService::RESULT_USER_SUCCESS,
            $this->tfaService->getTfaResult()
        );
    }

    /**
     * 無効2要素ユーザーテスト
     */
    public function testAuthDisabledTfaUser()
    {
        // プラグイン設定・メンバー・無効な2要素認証ユーザーを作成
        $Member = $this->createMember();
        $this->createConfig();
        $this->createTfaUser($Member, false);

        // 間違った認証コードでチェック、失敗するか
        $result = $this->tfaService->isValidCode($Member, '1234567');
        $this->assertFalse($result);
        $this->assertSame(
            JsysAsiTfaService::RESULT_USER_NONE,
            $this->tfaService->getTfaResult()
        );

        // 認証コードをnullにしてチェック、成功するか
        $result = $this->tfaService->isValidCode($Member, null);
        $this->assertTrue($result);
        $this->assertSame(
            JsysAsiTfaService::RESULT_USER_SUCCESS,
            $this->tfaService->getTfaResult()
        );
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

        // 認証コードをnullにしてチェック、失敗するか
        $result = $this->tfaService->isValidCode($Member, null);
        $this->assertFalse($result);
        $this->assertSame(
            JsysAsiTfaService::RESULT_FAILURE,
            $this->tfaService->getTfaResult()
        );

        // 間違った認証コードでチェック、失敗するか
        $result = $this->tfaService->isValidCode($Member, '1234567');
        $this->assertFalse($result);
        $this->assertSame(
            JsysAsiTfaService::RESULT_FAILURE,
            $this->tfaService->getTfaResult()
        );

        // 正しい認証コードを生成
        $code = $this->generateTotp($tfaUser);
        // OTP履歴へ認証コードを登録
        $this->insertOtpHistory($tfaUser, $code);

        // 使用済みの正しい認証コードでチェック、失敗するか
        $result = $this->tfaService->isValidCode($Member, $code);
        $this->assertFalse($result);
        $this->assertSame(
            JsysAsiTfaService::RESULT_TFA_USED,
            $this->tfaService->getTfaResult()
        );

        // OTP履歴を削除
        $this->deleteAllRows(['plg_jsys_asi_tfa_otp_history']);
        // 正しい認証コードでチェック、成功するか
        $result = $this->tfaService->isValidCode($Member, $code);
        $this->assertTrue($result);
        $this->assertSame(
            JsysAsiTfaService::RESULT_TFA_SUCCESS,
            $this->tfaService->getTfaResult()
        );
    }

    /**
     * 2要素認証過去OTPテスト
     */
    public function testAuthTfaPastOtp()
    {
        // プラグイン設定・メンバー・2要素認証ユーザーを作成
        $this->createConfig();
        $Member  = $this->createMember();
        $tfaUser = $this->createTfaUser($Member);

        // 正しい認証コードを生成
        $code = $this->generateTotp($tfaUser);

        // OTP履歴へ認証コードを1時間前で登録
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

        // 過去の認証コードでチェック、成功するか
        $result = $this->tfaService->isValidCode($Member, $code);
        $this->assertTrue($result);
        $this->assertSame(
            JsysAsiTfaService::RESULT_TFA_SUCCESS,
            $this->tfaService->getTfaResult()
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

        // 認証コードをマスターキーにしてチェック、成功するか
        $result = $this->tfaService->isValidCode($Member, $code);
        $this->assertTrue($result);
        $this->assertSame(
            JsysAsiTfaService::RESULT_MKEY_SUCCESS,
            $this->tfaService->getTfaResult()
        );

        // プラグイン設定を再読込
        $this->entityManager->refresh($Config);
        // 2要素認証が無効か
        $this->assertFalse($Config->getOptionTfa());
        // マスターキー・パスワード・ソルトがクリアされているか
        $this->assertNull($Config->getOptionTfaMasterKey());
        $this->assertNull($Config->getOptionTfaMasterKeyPassword());
        $this->assertNull($Config->getOptionTfaMasterKeySalt());
    }

    /**
     * スーパーバイザー認証無効値テスト
     */
//     public function testAuthSupervisorWithInvalidValue()
//     {
//         /*
//          * services.yaml
//          * parametersへjsys_asi_tfa_supervisor: falseを追加しておく
//          */

//         // プラグイン設定・メンバー・2要素認証ユーザーを作成
//         $Member = $this->createMember();
//         $this->createConfig();
//         $this->createTfaUser($Member);

//         // 認証コードをnullにしてチェック、失敗するか
//         $result = $this->tfaService->isValidCode($Member, null);
//         $this->assertFalse($result);
//         $this->assertSame(
//             JsysAsiTfaService::RESULT_FAILURE,
//             $this->tfaService->getTfaResult()
//         );

//         // 間違った認証コードでチェック、失敗するか
//         $result = $this->tfaService->isValidCode($Member, '1234567');
//         $this->assertFalse($result);
//         $this->assertSame(
//             JsysAsiTfaService::RESULT_FAILURE,
//             $this->tfaService->getTfaResult()
//         );
//     }

    /**
     * スーパーバイザー認証有効値テスト
     */
//     public function testAuthSupervisor()
//     {
//         /*
//          * services.yaml
//          * parametersへjsys_asi_tfa_supervisor: trueを追加しておく
//          */

//         // プラグイン設定・メンバー・2要素認証ユーザーを作成
//         $Member = $this->createMember();
//         $this->createConfig();
//         $this->createTfaUser($Member);

//         // 認証コードをnullにしてチェック、成功するか
//         $result = $this->tfaService->isValidCode($Member, null);
//         $this->assertTrue($result);
//         $this->assertSame(
//             JsysAsiTfaService::RESULT_SPV_SUCCESS,
//             $this->tfaService->getTfaResult()
//         );

//         // 間違った認証コードでチェック、失敗するか
//         $result = $this->tfaService->isValidCode($Member, '1234567');
//         $this->assertTrue($result);
//         $this->assertSame(
//             JsysAsiTfaService::RESULT_SPV_SUCCESS,
//             $this->tfaService->getTfaResult()
//         );
//     }


    /**
     * プラグイン設定を作成します。
     * @return \Plugin\JsysAsi\Entity\Config
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
