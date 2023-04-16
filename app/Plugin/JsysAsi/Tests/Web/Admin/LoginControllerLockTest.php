<?php
namespace Plugin\JsysAsi\Tests\Web\Admin;

use Faker\Generator;
use Eccube\Tests\Web\AbstractWebTestCase;
use Plugin\JsysAsi\Entity\Config;
use Plugin\JsysAsi\Entity\JsysAsiLoginHistory;
use Plugin\JsysAsi\Entity\JsysAsiLoginHistoryLockStatus;
use Plugin\JsysAsi\Entity\JsysAsiLoginHistoryStatus;
use Plugin\JsysAsi\Repository\ConfigRepository;
use Plugin\JsysAsi\Repository\JsysAsiLoginHistoryRepository;
use Plugin\JsysAsi\Repository\JsysAsiLockedIpAddressRepository;
use Plugin\JsysAsi\Entity\JsysAsiLockedIpAddress;

class LoginControllerLockTest extends AbstractWebTestCase
{
    /**
     * @var Generator
     */
    protected $faker;

    /**
     * @var ConfigRepository
     */
    protected $configRepo;

    /**
     * @var JsysAsiLockedIpAddressRepository
     */
    protected $lockedRepo;

    /**
     * @var JsysAsiLoginHistoryRepository
     */
    protected $historyRepo;


    /**
     * Setup method.
     * {@inheritDoc}
     * @see \Eccube\Tests\Web\AbstractWebTestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();

        $this->faker       = $this->getFaker();
        $this->configRepo  = self::$container->get(ConfigRepository::class);
        $this->lockedRepo  = self::$container->get(
            JsysAsiLockedIpAddressRepository::class
        );
        $this->historyRepo = self::$container->get(
            JsysAsiLoginHistoryRepository::class
        );

        $this->deleteAllRows([
            'plg_jsys_asi_login_history',
            'plg_jsys_asi_locked_ip_address',
        ]);
    }

    /**
     * ロックテスト
     */
    public function testLock()
    {
        // プラグイン設定、メンバー作成
        $Config = $this->createConfig();
        $Member = $this->createMember();

        // ロックされる1歩手前までログイン失敗を繰り返す
        for ($i = 1; $i < $Config->getOptionIpAddressLockCount(); ++$i) {
            $this->client->request('POST', $this->generateUrl('admin_login'), [
                'login_id'    => $Member->getLoginId(),
                'password'    => 'pass',
                '_csrf_token' => 'dummy',
            ]);
            $this->assertSame(302, $this->client->getResponse()->getStatusCode());
        }

        // ロック済みIPアドレスに登録されていないか
        $Records = $this->lockedRepo->findAll();
        $this->assertEmpty($Records);

        // 間違ったパスワードでログイン、失敗するか
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'    => $Member->getLoginId(),
            'password'    => 'pass',
            '_csrf_token' => 'dummy',
        ]);
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());

        // ロック済みIPアドレスに1件登録されているか
        $Records = $this->lockedRepo->findAll();
        $this->assertNotEmpty($Records);
        $this->assertCount(1, $Records);

        // 登録されたレコードのIPアドレスが一致するか
        /** @var \Plugin\JsysAsi\Entity\JsysAsiLockedIpAddress $Record */
        $Record = current($Records);
        $this->assertSame(
            $this->client->getRequest()->getClientIp(),
            $Record->getIpAddress()
        );
    }

    /**
     * 未ロックログインテスト
     */
    public function testUnlockLogin()
    {
        // プラグイン設定、メンバー作成
        $this->createConfig();
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

        // ログイン履歴からIPアドレスで抽出した最後のレコードを取得
        /** @var JsysAsiLoginHistory $Record  */
        $Record = $this->historyRepo->findOneBy(
            ['ip_address' => $this->client->getRequest()->getClientIp()],
            ['id' => 'DESC']
        );
        $this->assertNotEmpty($Record);

        // ステータスが成功、ロックステータスが未ロックになっているか
        $this->assertSame(
            JsysAsiLoginHistoryStatus::SUCCESS,
            $Record->getStatus()->getId()
        );
        $this->assertSame(
            JsysAsiLoginHistoryLockStatus::UNLOCKED,
            $Record->getLockStatus()->getId()
        );
    }

    /**
     * ロック済みログインテスト
     */
    public function testLockedLogin()
    {
        // プラグイン設定、メンバー作成、ロック済みへ自身を登録
        $this->createConfig();
        $Member = $this->createMember();
        $this->createLockedIp('127.0.0.1');

        // 正しいIDとパスワードでログイン、失敗するか
        $this->client->request('POST', $this->generateUrl('admin_login'), [
            'login_id'    => $Member->getLoginId(),
            'password'    => 'password',
            '_csrf_token' => 'dummy',
        ]);
        $this->assertSame(302, $this->client->getResponse()->getStatusCode());

        // ログイン履歴からIPアドレスで抽出した最後のレコードを取得
        /** @var JsysAsiLoginHistory $Record  */
        $Record = $this->historyRepo->findOneBy(
            ['ip_address' => $this->client->getRequest()->getClientIp()],
            ['id' => 'DESC']
        );
        $this->assertNotEmpty($Record);

        // ステータスが拒否、ロックステータスがロック済みになっているか
        $this->assertSame(
            JsysAsiLoginHistoryStatus::DENY,
            $Record->getStatus()->getId()
        );
        $this->assertSame(
            JsysAsiLoginHistoryLockStatus::LOCKED,
            $Record->getLockStatus()->getId()
        );
    }

    /**
     * スーパーバイザーテスト
     */
//     public function testAuthSupervisor()
//     {
//         /*
//          * services.yaml
//          * parametersへjsys_asi_lock_supervisor: trueを追加しておく
//          */

//         // プラグイン設定、メンバー作成、ロック済みへ自身を登録
//         $this->createConfig();
//         $Member = $this->createMember();
//         $this->createLockedIp('127.0.0.1');

//         // ログイン、成功するか
//         $this->client->request('POST', $this->generateUrl('admin_login'), [
//             'login_id'    => $Member->getLoginId(),
//             'password'    => 'password',
//             '_csrf_token' => 'dummy',
//         ]);
//         $this->assertNotNull(
//             self::$container->get('security.token_storage')->getToken()
//         );

//         // ログイン履歴からIPアドレスで抽出した最後のレコードを取得
//         /** @var JsysAsiLoginHistory $Record  */
//         $Record = $this->historyRepo->findOneBy(
//             ['ip_address' => $this->client->getRequest()->getClientIp()],
//             ['id' => 'DESC']
//         );
//         $this->assertNotEmpty($Record);

//         // ステータスが成功、ロックステータスが未ロックになっているか
//         $this->assertSame(
//             JsysAsiLoginHistoryStatus::SUCCESS,
//             $Record->getStatus()->getId()
//         );
//         $this->assertSame(
//             JsysAsiLoginHistoryLockStatus::UNLOCKED,
//             $Record->getLockStatus()->getId()
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
        }

        $Config
            ->setOptionTfa(false)
            ->setOptionTfaMasterKey(null)
            ->setOptionTfaMasterKeyPassword(null)
            ->setOptionTfaMasterKeySalt(null)
            ->setOptionLoginSuccessMail(false)
            ->setOptionLoginFailureMail(false)
            ->setOptionIpAddressLock(true)
            ->setOptionIpAddressLockCount(3);

        $this->entityManager->persist($Config);
        $this->entityManager->flush($Config);

        return $Config;
    }

    /**
     * ロック済みIPアドレスを登録します。
     * @param string $ipAddress
     * @return \Plugin\JsysAsi\Entity\JsysAsiLockedIpAddress
     */
    private function createLockedIp($ipAddress)
    {
        $LockedIp = new JsysAsiLockedIpAddress();
        $LockedIp->setIpAddress($ipAddress);

        $this->entityManager->persist($LockedIp);
        $this->entityManager->flush($LockedIp);

        return $LockedIp;
    }

}
