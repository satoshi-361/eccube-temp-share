<?php
namespace Plugin\JsysAsi\Tests\Web\Admin;

use Faker\Generator;
use Eccube\Repository\MemberRepository;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\JsysAsi\Entity\JsysAsiTfaOtpHistory;
use Plugin\JsysAsi\Entity\JsysAsiTfaUser;
use Plugin\JsysAsi\Repository\JsysAsiTfaOtpHistoryRepository;
use Plugin\JsysAsi\Repository\JsysAsiTfaUserRepository;
use Eccube\Entity\Member;

class MemberControllerTest extends AbstractAdminWebTestCase
{
    /**
     * @var Generator
     */
    protected $faker;

    /**
     * @var MemberRepository
     */
    protected $memberRepo;

    /**
     * @var JsysAsiTfaUserRepository
     */
    protected $tfaUserRepo;

    /**
     * @var JsysAsiTfaOtpHistoryRepository
     */
    protected $historyRepo;


    /**
     * Setup method.
     * {@inheritDoc}
     * @see \Eccube\Tests\Web\Admin\AbstractAdminWebTestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();

        $this->faker       = $this->getFaker();
        $this->memberRepo  = self::$container->get(MemberRepository::class);
        $this->tfaUserRepo = self::$container->get(JsysAsiTfaUserRepository::class);
        $this->historyRepo = self::$container->get(JsysAsiTfaOtpHistoryRepository::class);
    }

    /**
     * 2要素関連レコード削除テスト
     */
    public function testTfaDelete()
    {
        // メンバー・2要素認証ユーザーを作成、各エンティティのidを取得
        $Member1    = $this->createMember();
        $Member2    = $this->createMember();
        $Member1Id  = $Member1->getId();
        $Member2Id  = $Member2->getId();
        $tfaUser1   = $this->createTfaUser($Member1);
        $tfaUser2   = $this->createTfaUser($Member2);
        $tfaUser1Id = $tfaUser1->getId();
        $tfaUser2Id = $tfaUser2->getId();

        // OTP履歴を作成
        $this->createOtpHistories($tfaUser1);
        $this->createOtpHistories($tfaUser2);

        // メンバー1を削除
        $this->client->request('DELETE', $this->generateUrl(
            'admin_setting_system_member_delete',
            ['id' => $Member1Id]
        ));
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl(
            'admin_setting_system_member'
        )));
        $crawler = $this->client->followRedirect();
        $this->assertContains('削除しました', $crawler->filter('.alert')->html());

        // メンバー1の各種レコードは削除されているか
        $Member    = $this->memberRepo->find($Member1Id);
        $this->assertNull($Member);
        $tfaUser   = $this->tfaUserRepo->find($tfaUser1Id);
        $this->assertNull($tfaUser);
        $histories = $this->historyRepo->findBy(['tfa_user_id' => $tfaUser1Id]);
        $this->assertEmpty($histories);

        // メンバー2の各種レコードは存在しているか
        $Member    = $this->memberRepo->find($Member2Id);
        $this->assertNotNull($Member);
        $tfaUser   = $this->tfaUserRepo->find($tfaUser2Id);
        $this->assertNotNull($tfaUser);
        $histories = $this->historyRepo->findBy(['tfa_user_id' => $tfaUser2Id]);
        $this->assertNotEmpty($histories);
    }


    /**
     * メンバーと2要素認証ユーザーを作成します。
     * @param Member $Member
     * @return \Plugin\JsysAsi\Entity\JsysAsiTfaUser
     */
    private function createTfaUser(Member $Member)
    {
        $tfaUser = new JsysAsiTfaUser();
        $tfaUser
            ->setMemberId($Member->getId())
            ->setEnabled(true)
            ->setSecret($this->faker->password)
            ->setSecretPassword($this->faker->password)
            ->setSecretSalt($this->faker->password);

        $this->entityManager->persist($tfaUser);
        $this->entityManager->flush($tfaUser);
        return $tfaUser;
    }

    /**
     * 2要素認証ユーザーのOTP履歴を複数個作成します。
     * @param JsysAsiTfaUser $tfaUser
     */
    private function createOtpHistories(JsysAsiTfaUser $tfaUser)
    {
        for ($i = 0; $i < 6; ++$i) {
            $history = new JsysAsiTfaOtpHistory();
            $history
                ->setTfaUserId($tfaUser->getId())
                ->setOtp($this->generateCode());

            $this->entityManager->persist($history);
            $this->entityManager->flush($history);
        }
    }

    /**
     * 6桁のランダム数字文字列を取得します。
     * @return string
     */
    private function generateCode()
    {
        $length = 6;
        return substr(str_shuffle(str_repeat('0123456789', $length)), 0, $length);
    }

}
