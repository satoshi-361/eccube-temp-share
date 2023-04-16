<?php
namespace Plugin\JsysAsi\Tests\Web\Admin;

use Faker\Generator;
use OTPHP\TOTP;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\JsysAsi\Entity\JsysAsiTfaUser;
use Plugin\JsysAsi\Repository\JsysAsiTfaUserRepository;
use Plugin\JsysAsi\Service\JsysAsiCryptService;
use Plugin\JsysAsi\Service\JsysAsiTfaService;
use Eccube\Entity\Member;

class JsysAsiTfaUserControllerTest extends AbstractAdminWebTestCase
{
    /**
     * @var Generator
     */
    protected $faker;

    /**
     * @var GoogleAuthenticatorInterface
     */
    private $authenticator;

    /**
     * @var JsysAsiTfaUserRepository
     */
    private $tfaUserRepo;

    /**
     * @var JsysAsiCryptService
     */
    private $cryptService;

    /**
     * @var JsysAsiTfaService
     */
    private $tfaService;


    /**
     * Setup method.
     * {@inheritDoc}
     * @see \Eccube\Tests\Web\Admin\AbstractAdminWebTestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();

        $this->faker        = $this->getFaker();
        $this->tfaUserRepo  = self::$container->get(JsysAsiTfaUserRepository::class);
        $this->cryptService = self::$container->get(JsysAsiCryptService::class);
        $this->tfaService   = self::$container->get(JsysAsiTfaService::class);
    }

    /**
     * 2要素認証未登録メンバー一覧表示チェック
     */
    public function testTfaUserListUnregistered()
    {
        // 2要素認証ユーザーレコードがないメンバーを作成
        $Member = $this->createLastMember();

        // 一覧へアクセスできるか
        $crawler = $this->client->request('GET', $this->generateUrl(
            'admin_jsys_asi_tfa_user'
        ));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // タイトルが含まれているか
        $this->assertContains('ユーザー一覧', $crawler->html());

        // メンバー名が表示されているか
        $tr   = 'table.table-sm tr:first-of-type';
        $html = $crawler->filter("{$tr} td:nth-of-type(2)")->html();
        $this->assertContains($Member->getName(), $html);

        // ステータスが無効か
        $html = $crawler->filter("{$tr} td:nth-of-type(3)")->html();
        $this->assertContains('text-danger', $html);

        // 設定ボタンが有効か
        $html = $crawler
            ->filter("{$tr} td:nth-of-type(4) > div div:first-of-type")
            ->html();
        $this->assertNotContains('disabled', $html);

        // QRコードボタンが無効か
        $html = $crawler
            ->filter("{$tr} td:nth-of-type(4) > div div:nth-of-type(2)")
            ->html();
        $this->assertContains('disabled', $html);

        // 削除ボタンが無効か
        $html = $crawler
            ->filter("{$tr} td:nth-of-type(4) > div div:last-of-type")
            ->html();
        $this->assertContains('disabled', $html);
    }

    /**
     * 2要素認証無効メンバー一覧表示チェック
     */
    public function testTfaUserListDisabled()
    {
        // 無効になっている2要素認証ユーザーを取得
        $Member  = $this->createLastMember();
        $tfaUser = $this->createTfaUser($Member, false);

        // 一覧へアクセスできるか
        $crawler = $this->client->request('GET', $this->generateUrl(
            'admin_jsys_asi_tfa_user'
        ));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // タイトルが含まれているか
        $this->assertContains('ユーザー一覧', $crawler->html());

        // メンバー名が表示されているか
        $tr   = 'table.table-sm tr:first-of-type';
        $html = $crawler->filter("{$tr} td:nth-of-type(2)")->html();
        $this->assertContains($Member->getName(), $html);

        // ステータスが無効か
        $html = $crawler->filter("{$tr} td:nth-of-type(3)")->html();
        $this->assertContains('text-danger', $html);

        // 設定ボタンが有効か
        $html = $crawler
            ->filter("{$tr} td:nth-of-type(4) > div div:first-of-type")
            ->html();
        $this->assertNotContains('disabled', $html);

        // QRコードボタンが無効か
        $html = $crawler
            ->filter("{$tr} td:nth-of-type(4) > div div:nth-of-type(2)")
            ->html();
        $this->assertContains('disabled', $html);

        // 削除ボタンが無効か
        $html = $crawler
            ->filter("{$tr} td:nth-of-type(4) > div div:last-of-type")
            ->html();
        $this->assertContains('disabled', $html);
    }

    /**
     * 2要素認証有効メンバー一覧表示チェック
     */
    public function testTfaUserListEnabled()
    {
        // 有効になっている2要素認証ユーザーを取得
        $Member  = $this->createLastMember();
        $tfaUser = $this->createTfaUser($Member);

        // 一覧へアクセスできるか
        $crawler = $this->client->request('GET', $this->generateUrl(
            'admin_jsys_asi_tfa_user'
        ));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // タイトルが含まれているか
        $this->assertContains('ユーザー一覧', $crawler->html());

        // メンバー名が表示されているか
        $tr   = 'table.table-sm tr:first-of-type';
        $html = $crawler->filter("{$tr} td:nth-of-type(2)")->html();
        $this->assertContains($Member->getName(), $html);

        // ステータスが有効か
        $html = $crawler->filter("{$tr} td:nth-of-type(3)")->html();
        $this->assertContains('text-info', $html);

        // 設定ボタンが無効か
        $html = $crawler
            ->filter("{$tr} td:nth-of-type(4) > div div:first-of-type")
            ->html();
        $this->assertContains('disabled', $html);

        // QRコードボタンが有効か
        $html = $crawler
            ->filter("{$tr} td:nth-of-type(4) > div div:nth-of-type(2)")
            ->html();
        $this->assertNotContains('disabled', $html);
        // QRコードが表示されているか
        $this->assertContains($this->tfaService->getQRCode($tfaUser), $html);

        // 削除ボタンが有効か
        $html = $crawler
            ->filter("{$tr} td:nth-of-type(4) > div div:last-of-type")
            ->html();
        $this->assertNotContains('disabled', $html);
    }


    /**
     * 存在しないメンバー登録画面表示チェック
     */
    public function testTfaUserEditMemberNotFound()
    {
        $this->client->request('GET', $this->generateUrl(
            'admin_jsys_asi_tfa_user_edit',
            ['member_id' => 99999]
        ));
        $this->expected = 404;
        $this->actual   = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    /**
     * 登録画面OTP必須チェック
     */
    public function testTfaUserEditOtpRequire()
    {
        // 2要素認証ユーザーレコードがないメンバーを作成
        $Member = $this->createLastMember();

        // 編集へアクセスできるか
        $crawler = $this->client->request('GET', $this->generateUrl(
            'admin_jsys_asi_tfa_user_edit',
            ['member_id' => $Member->getId()]
        ));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // タイトルが含まれているか
        $this->assertContains('ユーザー設定', $crawler->html());

        // 2要素認証ユーザーが作成されているか
        /** @var JsysAsiTfaUser $tfaUser */
        $tfaUser = $this->tfaUserRepo->findOneBy(['member_id' => $Member->getId()]);
        $this->assertNotNull($tfaUser);

        // 無効な2要素認証ユーザーか
        $this->assertFalse($tfaUser->getEnabled());

        // メンバー名が表示されているか
        $this->assertContains($Member->getName(), $crawler->html());

        // QRコードが表示されているか
        $this->assertContains(
            $this->tfaService->getQRCode($tfaUser),
            $crawler->html()
        );

        // フォームデータを作成して送信
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_jsys_asi_tfa_user_edit', [
                'member_id' => $Member->getId(),
            ]),
            ['jsys_asi_admin_tfa_user' => ['otp' => null]]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 必須メッセージが表示されているか
        $this->assertContains('入力されていません。', $crawler->html());
    }

    /**
     * 登録画面OTP失敗・桁数チェック
     */
    public function testTfaUserEditOtpFailure()
    {
        // 2要素認証ユーザーレコードがないメンバーを作成
        $Member = $this->createLastMember();

        // 編集へアクセスできるか
        $crawler = $this->client->request('GET', $this->generateUrl(
            'admin_jsys_asi_tfa_user_edit',
            ['member_id' => $Member->getId()]
        ));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // タイトルが含まれているか
        $this->assertContains('ユーザー設定', $crawler->html());

        // 2要素認証ユーザーが作成されているか
        /** @var JsysAsiTfaUser $tfaUser */
        $tfaUser = $this->tfaUserRepo->findOneBy(['member_id' => $Member->getId()]);
        $this->assertNotNull($tfaUser);

        // 無効な2要素認証ユーザーか
        $this->assertFalse($tfaUser->getEnabled());

        // メンバー名が表示されているか
        $this->assertContains($Member->getName(), $crawler->html());

        // QRコードが表示されているか
        $this->assertContains(
            $this->tfaService->getQRCode($tfaUser),
            $crawler->html()
        );

        // フォームを取得して送信
        $form = $crawler->selectButton('登録')->form();
        $form['jsys_asi_admin_tfa_user[otp]'] = '1234567';
        $crawler = $this->client->submit($form);

        // 桁数エラーが表示されているか
        $this->assertContains(
            '値が長すぎます。6文字以内でなければなりません。',
            $crawler->html()
        );

        // 失敗エラーが表示されているか
        $this->assertContains(
            '認証に失敗しました。サーバー側の時刻が正しくない可能性があります。',
            $crawler->html()
        );
    }

    /**
     * 登録画面登録成功チェック
     */
    public function testTfaUserEditOtpSuccess()
    {
        // 2要素認証ユーザーレコードがないメンバーを作成
        $Member = $this->createLastMember();

        // 編集へアクセスできるか
        $crawler = $this->client->request('GET', $this->generateUrl(
            'admin_jsys_asi_tfa_user_edit',
            ['member_id' => $Member->getId()]
        ));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // タイトルが含まれているか
        $this->assertContains('ユーザー設定', $crawler->html());

        // 2要素認証ユーザーが作成されているか
        /** @var JsysAsiTfaUser $tfaUser */
        $tfaUser = $this->tfaUserRepo->findOneBy(['member_id' => $Member->getId()]);
        $this->assertNotNull($tfaUser);

        // 無効な2要素認証ユーザーか
        $this->assertFalse($tfaUser->getEnabled());

        // メンバー名が表示されているか
        $this->assertContains($Member->getName(), $crawler->html());

        // QRコードが表示されているか
        $this->assertContains(
            $this->tfaService->getQRCode($tfaUser),
            $crawler->html()
        );

        // 正解のOTPを生成
        $otp = $this->generateTotp($tfaUser);

        // フォームを取得、OTPを設定して送信
        $form = $crawler->selectButton('登録')->form();
        $form['jsys_asi_admin_tfa_user[otp]'] = $otp;
        $crawler = $this->client->submit($form);

        // 保存メッセージチェック
        $crawler = $this->client->followRedirect();
        $this->assertContains('保存しました', $crawler->filter('.alert')->html());

        // 保存された2要素認証ユーザーが有効か
        /** @var JsysAsiTfaUser $savedTfaUser */
        $savedTfaUser = $this->tfaUserRepo->find($tfaUser->getId());
        $this->assertTrue($savedTfaUser->getEnabled());
    }


    /**
     * 存在しない2要素認証ユーザー削除チェック
     */
    public function testTfaUserDeleteIdNotFound()
    {
        $this->client->request('DELETE', $this->generateUrl(
            'admin_jsys_asi_tfa_user_delete',
            ['id' => 99999]
        ));
        $this->expected = 404;
        $this->actual   = $this->client->getResponse()->getStatusCode();
        $this->verify();
    }

    /**
     * 2要素認証ユーザー削除チェック
     */
    public function testTfaUserDelete()
    {
        // 有効な2要素認証ユーザーを作成
        $Member  = $this->createLastMember();
        $tfaUser = $this->createTfaUser($Member);
        $id      = $tfaUser->getId();

        // 削除
        $this->client->request('DELETE', $this->generateUrl(
            'admin_jsys_asi_tfa_user_delete',
            ['id' => $id]
        ));
        $this->assertTrue($this->client->getResponse()->isRedirection());

        // 削除メッセージチェック
        $crawler = $this->client->followRedirect();
        $this->assertContains('削除しました', $crawler->filter('.alert')->html());

        // 2要素認証ユーザーが存在していないか
        $this->assertNull($this->tfaUserRepo->find($id));
    }


    /**
     * 並び順が最後のメンバーを作成します。
     * @return \Eccube\Entity\Member
     */
    private function createLastMember()
    {
        // メンバーを作成
        $Member = $this->createMember();
        $Member->setSortNo(99);
        $this->entityManager->persist($Member);
        $this->entityManager->flush($Member);

        return $Member;
    }

    /**
     * メンバーと2要素認証ユーザーを作成します。
     * @param Member $Member
     * @param boolean $enabled
     * @return \Plugin\JsysAsi\Entity\JsysAsiTfaUser
     */
    private function createTfaUser(Member $Member, $enabled = true)
    {
        // シークレット・パスワード・ソルトを生成、シークレットを暗号化
        $secret    = $this->tfaService->generateSecret();
        $password  = $this->cryptService->createPassword();
        $salt      = $this->cryptService->createSalt();
        $encrypted = $this->cryptService->encrypt($secret, $password, $salt);

        // 2要素認証ユーザーを作成
        $tfaUser = new JsysAsiTfaUser();
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

}
