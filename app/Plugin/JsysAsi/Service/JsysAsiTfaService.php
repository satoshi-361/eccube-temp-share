<?php
namespace Plugin\JsysAsi\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Member;
use Eccube\Repository\BaseInfoRepository;
use Plugin\JsysAsi\Entity\Config;
use Plugin\JsysAsi\Entity\JsysAsiTfaUser;
use Plugin\JsysAsi\Entity\JsysAsiTfaOtpHistory;
use Plugin\JsysAsi\Util\JsysAsiTwoFactorUserUtil;
use Plugin\JsysAsi\Repository\ConfigRepository;
use Plugin\JsysAsi\Repository\JsysAsiTfaUserRepository;
use Plugin\JsysAsi\Repository\JsysAsiTfaOtpHistoryRepository;
use Eccube\Repository\MemberRepository;
use Endroid\QrCode\QrCode;
use Plugin\JsysAsi\Util\Authenticator\Security\JsysGoogleAuthenticator;
use Plugin\JsysAsi\Util\Authenticator\Security\JsysGoogleTotpFactory;

/**
 * 2要素認証サービス
 * @author manabe
 *
 */
class JsysAsiTfaService
{
    /**
     * デフォルト 認証コード桁数
     * @var int
     */
    const DEFAULT_TFA_DIGITS = 6;

    /**
     * デフォルト 有効なコード数
     */
    const DEFAULT_TFA_WINDOW = 1;

    /**
     * 認証結果 ユーザー未設定：失敗(無効含む)
     * @var int
     */
    const RESULT_USER_NONE = 7;

    /**
     * 認証結果 ユーザー未設定：成功
     * @var int
     */
    const RESULT_USER_SUCCESS = 0;

    /**
     * 認証結果 2要素：成功
     * @var int
     */
    const RESULT_TFA_SUCCESS = 1;

    /**
     * 認証結果 2要素：使用済
     * @var int
     */
    const RESULT_TFA_USED = 8;

    /**
     * 認証結果 マスターキー：成功
     * @var int
     */
    const RESULT_MKEY_SUCCESS = 2;

    /**
     * 認証結果 スーパーバイザー：成功
     * @var int
     */
    const RESULT_SPV_SUCCESS = 3;

    /**
     * 認証結果 失敗
     * @var int
     */
    const RESULT_FAILURE = 9;


    /**
     * @var JsysGoogleAuthenticator
     */
    private $authenticator;

    /**
     * @var JsysAsiCryptService
     */
    private $cryptService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    /**
     * @var MemberRepository
     */
    private $memberRepo;

    /**
     * @var JsysAsiTfaUserRepository
     */
    private $tfaUserRepo;

    /**
     * @var JsysAsiTfaOtpHistoryRepository
     */
    private $tfaOtpHistoryRepo;

    /**
     * @var BaseInfo
     */
    private $BaseInfo;

    /**
     * @var Config
     */
    private $Config;


    /**
     * 認証結果
     * @var int
     */
    private $tfa_result;

    /**
     * 認証結果を取得します。
     * @return int
     */
    public function getTfaResult()
    {
        return $this->tfa_result;
    }


    /**
     * JsysAsiTfaService constructor.
     * @param EntityManagerInterface $entityManager
     * @param EccubeConfig $eccubeConfig
     * @param BaseInfoRepository $baseInfoRepo
     * @param JsysAsiCryptService $cryptService
     * @param MemberRepository $memberRepo
     * @param ConfigRepository $configRepo
     * @param JsysAsiTfaUserRepository $tfaUserRepo
     * @param JsysAsiTfaOtpHistoryRepository $tfaOtpHistoryRepo
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        EccubeConfig $eccubeConfig,
        BaseInfoRepository $baseInfoRepo,
        JsysAsiCryptService $cryptService,
        MemberRepository $memberRepo,
        ConfigRepository $configRepo,
        JsysAsiTfaUserRepository $tfaUserRepo,
        JsysAsiTfaOtpHistoryRepository $tfaOtpHistoryRepo
    ) {
        $this->entityManager     = $entityManager;
        $this->eccubeConfig      = $eccubeConfig;
        $this->cryptService      = $cryptService;
        $this->memberRepo        = $memberRepo;
        $this->tfaUserRepo       = $tfaUserRepo;
        $this->tfaOtpHistoryRepo = $tfaOtpHistoryRepo;
        $this->BaseInfo          = $baseInfoRepo->get();
        $this->Config            = $configRepo->get();

        $this->initializeAuthenticator();
    }

    /**
     * 2要素認証の初期設定を行います。
     * @return void
     */
    private function initializeAuthenticator()
    {
        $params = [
            'jsys_asi_tfa_server' => null,
            'jsys_asi_tfa_issuer' => $this->BaseInfo->getShopName(),
            'jsys_asi_tfa_digits' => self::DEFAULT_TFA_DIGITS,
            'jsys_asi_tfa_window' => self::DEFAULT_TFA_WINDOW,
        ];
        $keys = array_keys($params);
        foreach ($keys as $key) {
            if (!empty($this->eccubeConfig[$key])) {
                $params[$key] = $this->eccubeConfig[$key];
            }
        }

        $this->authenticator = new JsysGoogleAuthenticator(
            new JsysGoogleTotpFactory(
                $params['jsys_asi_tfa_server'],
                $params['jsys_asi_tfa_issuer'],
                $params['jsys_asi_tfa_digits']
            ),
            $params['jsys_asi_tfa_window']
        );
    }

    /**
     * シークレットを生成します。
     * @return string
     */
    public function generateSecret()
    {
        return $this->authenticator->generateSecret();
    }

    /**
     * QRコードを取得します。
     * @param JsysAsiTfaUser $jsysAsiTfaUser
     * @return mixed
     */
    public function getQRCode(JsysAsiTfaUser $jsysAsiTfaUser)
    {
        $user   = new JsysAsiTwoFactorUserUtil(
            $this->cryptService,
            $jsysAsiTfaUser,
            $this->getMemberName($jsysAsiTfaUser->getMemberId())
        );
        $qrCode = new QrCode($this->authenticator->getQRContent($user));
        return $qrCode->writeDataUri();
    }

    /**
     * 認証コードが正しいか調べます。
     * @param JsysAsiTfaUser $jsysAsiTfaUser
     * @param string $code
     * @return boolean
     */
    public function checkCode(JsysAsiTfaUser $jsysAsiTfaUser, $code)
    {
        if (is_null($code)) {
            return false;
        }
        $user = new JsysAsiTwoFactorUserUtil(
            $this->cryptService,
            $jsysAsiTfaUser,
            $this->getMemberName($jsysAsiTfaUser->getMemberId())
        );
        return $this->authenticator->checkCode($user, $code);
    }

    /**
     * 認証コードが使用済みか調べます。
     * @param JsysAsiTfaUser $jsysAsiTfaUser
     * @param string $code
     * @return boolean
     */
    public function checkUsedCode(JsysAsiTfaUser $jsysAsiTfaUser, $code)
    {
        $min = $this->eccubeConfig['jsys_asi_tfa_otp_banned_min'];
        $min = empty($min) ? 5 : $min;
        return $this->tfaOtpHistoryRepo->isUsedOtp($jsysAsiTfaUser, $code, $min);
    }

    /**
     * 正しいマスターキーか調べます。
     * @param string $code
     * @throws \Exception
     * @return boolean
     */
    public function checkMasterKey($code)
    {
        if (!$this->Config) {
            throw new \Exception('JsysAsi JsysAsiTfaService Config not found.');
        }
        if (!$this->Config->getOptionTfaMasterKey()) {
            return false;
        }

        // マスターキーを復号化
        $key = $this->cryptService->decrypt(
            $this->Config->getOptionTfaMasterKey(),
            $this->Config->getOptionTfaMasterKeyPassword(),
            $this->Config->getOptionTfaMasterKeySalt()
        );
        if ($key != $code) {
            return false;
        }

        // マスターキーと認証コードが一致するため、2要素認証を無効へ更新
        $this->Config
            ->setOptionTfa(false)
            ->setOptionTfaMasterKey(null)
            ->setOptionTfaMasterKeyPassword(null)
            ->setOptionTfaMasterKeySalt(null);

        $this->entityManager->persist($this->Config);
        $this->entityManager->flush($this->Config);
        return true;
    }

    /**
     * スーパーバイザーか調べます。
     * @return boolean
     */
    public function checkSupervisor()
    {
        if (!$this->eccubeConfig->has('jsys_asi_tfa_supervisor')) {
            return false;
        }
        if (true === $this->eccubeConfig['jsys_asi_tfa_supervisor']) {
            return true;
        }
        return false;
    }

    /**
     * 有効な認証コードか調べます。
     * @param Member $Member
     * @param string $code
     * @return boolean
     */
    public function isValidCode(Member $Member, $code)
    {
        // 2要素認証ユーザーを取得
        $jsysAsiTfaUser = $this->tfaUserRepo->findOneBy([
            'member_id' => $Member->getId(),
            'enabled'   => true,
        ]);

        // ユーザー未設定または無効なユーザー
        if (!$jsysAsiTfaUser) {
            if (is_null($code) || '' === $code) {
                // コード未入力
                $this->tfa_result = self::RESULT_USER_SUCCESS;
                return true;
            } else {
                // コード入力
                $this->tfa_result = self::RESULT_USER_NONE;
                return false;
            }
        }

        // 2要素認証チェック
        if ($this->checkCode($jsysAsiTfaUser, $code)) {
            if ($this->checkUsedCode($jsysAsiTfaUser, $code)) {
                // 使用済みコード
                $this->tfa_result = self::RESULT_TFA_USED;
                log_info('JsysAsi Tfa 使用済みコード', [
                    'member_id' => $Member->getId(),
                    'code'      => $code,
                ]);
                return false;
            } else {
                // 未使用の正しいコード
                $this->tfa_result = self::RESULT_TFA_SUCCESS;
                $this->insertTfaOtpHistory($jsysAsiTfaUser, $code);
                $this->tfaOtpHistoryRepo->deleteOldRecordsByDays();
                return true;
            }
        }

        // マスターキーチェック
        if ($this->checkMasterKey($code)) {
            $this->tfa_result = self::RESULT_MKEY_SUCCESS;
            log_info('JsysAsi Tfa マスターキー使用', ['member_id' => $Member->getId()]);
            return true;
        }

        // スーパーバイザーチェック
        if ($this->checkSupervisor()) {
            $this->tfa_result = self::RESULT_SPV_SUCCESS;
            log_info('JsysAsi Tfa Supervisor', ['member_id' => $Member->getId()]);
            return true;
        }

        // 不正な認証コード
        $this->tfa_result = self::RESULT_FAILURE;
        return false;
    }


    /**
     * OTP履歴へ新規登録を行います。
     * @param JsysAsiTfaUser $jsysAsiTfaUser
     * @param string $code
     */
    private function insertTfaOtpHistory(JsysAsiTfaUser $jsysAsiTfaUser, $code)
    {
        $history = new JsysAsiTfaOtpHistory();
        $history
            ->setTfaUserId($jsysAsiTfaUser->getId())
            ->setOtp($code)
            ->setCreateDate(new \DateTime())
            ->setUpdateDate(new \DateTime());

        $this->entityManager->persist($history);
        $this->entityManager->flush($history);
    }

    /**
     * メンバー名を取得します。
     * @param int $member_id
     * @throws \Exception
     * @return string|NULL
     */
    private function getMemberName($member_id)
    {
        /** @var \Eccube\Entity\Member $Member */
        $Member = $this->memberRepo->find($member_id);
        if (!$Member) {
            throw new \Exception('Member not found.');
        }
        return $Member->getName();
    }

}
