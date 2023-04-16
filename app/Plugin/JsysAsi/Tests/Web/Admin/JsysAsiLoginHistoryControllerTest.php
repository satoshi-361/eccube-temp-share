<?php
namespace Plugin\JsysAsi\Tests\Web\Admin;

use Symfony\Component\DomCrawler\Crawler;
use Faker\Generator;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\JsysAsi\Entity\JsysAsiLoginHistoryStatus;
use Plugin\JsysAsi\Entity\JsysAsiLoginHistoryTfaStatus;
use Plugin\JsysAsi\Entity\JsysAsiLoginHistoryLockStatus;
use Plugin\JsysAsi\Entity\JsysAsiLoginHistory;

class JsysAsiLoginHistoryControllerTest extends AbstractAdminWebTestCase
{
    /**
     * @var Generator
     */
    protected $faker;


    /**
     * setUp method.
     * {@inheritDoc}
     * @see \Eccube\Tests\Web\Admin\AbstractAdminWebTestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();

        $this->faker = $this->getFaker();

        $this->deleteAllRows(['plg_jsys_asi_login_history']);

        $this->createHistories();
    }

    /**
     * ルーティングテスト
     */
    public function testRouting()
    {
        // ログイン履歴にアクセスできるか
        $this->client->request('GET', $this->generateUrl(
            'admin_jsys_asi_login_history'
        ));
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    /**
     * 初期表示テスト
     */
    public function testIndex()
    {
        // ログイン履歴にアクセスできるか
        $crawler = $this->client->request('GET', $this->generateUrl(
            'admin_jsys_asi_login_history'
        ));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 検索結果の件数が作成した履歴と一致するか
        $this->assertContains(
            '検索結果：11件が該当しました',
            $crawler->filter('#search_form #search_total_count')->text()
        );
    }

    /**
     * ログインID・IPアドレス検索テスト
     */
    public function testSearchWithMulti()
    {
        // ログインIDで抽出
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_jsys_asi_login_history'),
            [
                'jsys_asi_admin_login_history_search' => [
                    '_token' => 'dummy',
                    'multi'  => 'test1',
                ],
            ]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 検索結果の件数が予想と一致するか
        $this->assertContains(
            '検索結果：5件が該当しました',
            $crawler->filter('#search_form #search_total_count')->text()
        );

        // IPアドレスで抽出
        $ip = '172.16.2.37';
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_jsys_asi_login_history'),
            [
                'jsys_asi_admin_login_history_search' => [
                    '_token' => 'dummy',
                    'multi'  => $ip,
                ],
            ]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 検索結果の件数が予想と一致するか
        $this->assertContains(
            '検索結果：3件が該当しました',
            $crawler->filter('#search_form #search_total_count')->text()
        );

        // 抽出後のIPアドレス列が検索した値になっているか
        $values = $this->getHistoryColumnValues($crawler, 4);
        foreach ($values as $value) {
            $this->assertSame($ip, $value);
        }
    }

    /**
     * ログイン日時検索テスト
     */
    public function testSearchWithLoginDate()
    {
        // ログイン日時で抽出
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_jsys_asi_login_history'),
            [
                'jsys_asi_admin_login_history_search' => [
                    '_token'           => 'dummy',
                    'login_date_start' => '2021-05-04 00:01:00',
                    'login_date_end'   => '2021-05-04 00:02:00',
                ],
            ]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 検索結果の件数が予想と一致するか
        $this->assertContains(
            '検索結果：2件が該当しました',
            $crawler->filter('#search_form #search_total_count')->text()
        );
    }

    /**
     * 結果検索テスト
     */
    public function testSearchWithStatus()
    {
        // ログイン結果=1:成功で抽出
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_jsys_asi_login_history'),
            [
                'jsys_asi_admin_login_history_search' => [
                    '_token' => 'dummy',
                    'status' => [
                        JsysAsiLoginHistoryStatus::SUCCESS,
                    ],
                ],
            ]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 検索結果の件数が予想と一致するか
        $this->assertContains(
            '検索結果：4件が該当しました',
            $crawler->filter('#search_form #search_total_count')->text()
        );

        // 抽出後のログイン結果列に成功アイコンが表示されているか
        $values = $this->getHistoryColumnValues($crawler, 5);
        foreach ($values as $value) {
            $this->assertContains('fa-check', $value);
        }

        // ログイン結果=2:拒否・9:失敗で抽出
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_jsys_asi_login_history'),
            [
                'jsys_asi_admin_login_history_search' => [
                    '_token' => 'dummy',
                    'status' => [
                        JsysAsiLoginHistoryStatus::DENY,
                        JsysAsiLoginHistoryStatus::FAILURE,
                    ],
                ],
            ]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 検索結果の件数が予想と一致するか
        $this->assertContains(
            '検索結果：7件が該当しました',
            $crawler->filter('#search_form #search_total_count')->text()
        );

        // 抽出後のログイン結果列に拒否または失敗アイコンが表示されているか
        $values = $this->getHistoryColumnValues($crawler, 5);
        foreach ($values as $value) {
            $this->assertRegExp('/(fa-exclamation|fa-times)/', $value);
        }
    }

    /**
     * 2要素認証検索テスト
     */
    public function testSearchWithTfaStatus()
    {
        // 2要素=2:有効OTPで抽出
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_jsys_asi_login_history'),
            [
                'jsys_asi_admin_login_history_search' => [
                    '_token'     => 'dummy',
                    'tfa_status' => [
                        JsysAsiLoginHistoryTfaStatus::VALID,
                    ],
                ],
            ]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 検索結果の件数が予想と一致するか
        $this->assertContains(
            '検索結果：4件が該当しました',
            $crawler->filter('#search_form #search_total_count')->text()
        );

        // 抽出後の2要素列に成功アイコンが表示されているか
        $values = $this->getHistoryColumnValues($crawler, 6);
        foreach ($values as $value) {
            $this->assertContains('fa-check', $value);
        }

        // 2要素=3:無効OTP・4:使用済OTPで抽出
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_jsys_asi_login_history'),
            [
                'jsys_asi_admin_login_history_search' => [
                    '_token'     => 'dummy',
                    'tfa_status' => [
                        JsysAsiLoginHistoryTfaStatus::INVALID,
                        JsysAsiLoginHistoryTfaStatus::USED,
                    ],
                ],
            ]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 検索結果の件数が予想と一致するか
        $this->assertContains(
            '検索結果：5件が該当しました',
            $crawler->filter('#search_form #search_total_count')->text()
        );

        // 抽出後の2要素列に無効または使用済みアイコンが表示されているか
        $values = $this->getHistoryColumnValues($crawler, 6);
        foreach ($values as $value) {
            $this->assertRegExp('/(fa-exclamation|fa-times)/', $value);
        }
    }

    /**
     * ロック検索テスト
     */
    public function testSearchWithLockStatus()
    {
        // ロック=2:未ロックで抽出
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_jsys_asi_login_history'),
            [
                'jsys_asi_admin_login_history_search' => [
                    '_token'      => 'dummy',
                    'lock_status' => [
                        JsysAsiLoginHistoryLockStatus::UNLOCKED,
                    ]
                ],
            ]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 検索結果の件数が予想と一致するか
        $this->assertContains(
            '検索結果：7件が該当しました',
            $crawler->filter('#search_form #search_total_count')->text()
        );

        // 抽出後のロック列に成功アイコンが表示されているか
        $values = $this->getHistoryColumnValues($crawler, 7);
        foreach ($values as $value) {
            $this->assertContains('fa-check', $value);
        }

        // ロック=3:ロック済みで抽出
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_jsys_asi_login_history'),
            [
                'jsys_asi_admin_login_history_search' => [
                    '_token'      => 'dummy',
                    'lock_status' => [
                        JsysAsiLoginHistoryLockStatus::LOCKED,
                    ]
                ],
            ]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 検索結果の件数が予想と一致するか
        $this->assertContains(
            '検索結果：3件が該当しました',
            $crawler->filter('#search_form #search_total_count')->text()
        );

        // 抽出後のロック列に無効アイコンが表示されているか
        $values = $this->getHistoryColumnValues($crawler, 7);
        foreach ($values as $value) {
            $this->assertContains('fa-times', $value);
        }
    }

    /**
     * レコードなしテスト
     */
    public function testNoRecord()
    {
        // 作成したデータが対象外になる条件で抽出
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_jsys_asi_login_history'),
            [
                'jsys_asi_admin_login_history_search' => [
                    '_token'           => 'dummy',
                    'login_date_start' => '2021-05-20 00:00:00',
                ],
            ]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 検索結果の件数が予想と一致するか
        $this->assertContains(
            '検索結果：0件が該当しました',
            $crawler->filter('#search_form #search_total_count')->text()
        );

        // データが無い場合のメッセージが表示されているか
        $this->assertContains(
            '検索条件に合致するデータが見つかりませんでした',
            $crawler->html()
        );
        $this->assertContains(
            '検索条件を変えて、再度検索をお試しください',
            $crawler->html()
        );
        $this->assertContains(
            '[詳細検索]も試してみましょう',
            $crawler->html()
        );
    }

    /**
     * 検索エラーテスト
     */
    public function testSearchError()
    {
        // 検索項目が検証失敗になるように送信
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_jsys_asi_login_history'),
            [
                'jsys_asi_admin_login_history_search' => [
                    '_token'           => 'dummy',
                    'login_date_start' => 'あいうえお',
                ],
            ]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 検証失敗メッセージが表示されているか
        $sele = '#jsys_asi_admin_login_history_search_login_date_start + '
              . 'span.invalid-feedback .form-error-message';
        $this->assertSame('有効な値ではありません。', $crawler->filter($sele)->text());

        // エラーの場合のメッセージが表示されているか
        $this->assertContains(
            '検索条件に誤りがあります',
            $crawler->html()
        );
        $this->assertContains(
            '検索条件を変えて、再度検索をお試しください',
            $crawler->html()
        );
    }

    /**
     * 表示件数・ページングテスト
     */
    public function testPaging()
    {
        // 2ページになるように表示件数を10件、ページ数を2にして送信
        $crawler = $this->client->request(
            'GET',
            $this->generateUrl('admin_jsys_asi_login_history_page', ['page_no' => 2]),
            ['page_count' => 10]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 件数は正しいか
        $this->assertContains(
            '検索結果：11件が該当しました',
            $crawler->filter('#search_form #search_total_count')->text()
        );

        // 表示件数は10件になっているか
        $this->assertContains(
            '10件',
            $crawler->filter('select.custom-select > option:selected')->text()
        );

        // ページリンクの最後を取得
        $paging = $crawler->filter('ul.pagination .page-item')->last();
        // ページリンクがアクティブになっているか
        $this->assertContains('active', $paging->parents()->html());
        // ページリンクが2ページ目になっているか
        $this->expected = 2;
        $this->actual   = intval($paging->text());
        $this->verify();
    }


    /**
     * ログイン履歴データを複数作成します。
     */
    private function createHistories()
    {
        $datas = $this->getHistoriesData();
        foreach ($datas as $data) {
            $status = $this->entityManager->find(
                JsysAsiLoginHistoryStatus::class,
                $data['status']
            );
            $tfaStatus = $this->entityManager->find(
                JsysAsiLoginHistoryTfaStatus::class,
                $data['tfa_status']
            );
            $lockStatus = $this->entityManager->find(
                JsysAsiLoginHistoryLockStatus::class,
                $data['lock_status']
            );

            $History = new JsysAsiLoginHistory();
            $History
                ->setLoginDate($data['date'])
                ->setLoginId($data['id'])
                ->setIpAddress($data['ip'])
                ->setStatus($status)
                ->setTfaStatus($tfaStatus)
                ->setLockStatus($lockStatus)
                ->setUserAgent($data['ua']);

            $this->entityManager->persist($History);
            $this->entityManager->flush($History);
        }
    }

    /**
     * ログイン履歴データを取得します。
     * @return array
     */
    private function getHistoriesData()
    {
        return [
            [
                'date'        => new \DateTime('2021-05-01 00:00:00'),
                'id'          => 'test1',
                'ip'          => '172.16.2.37',
                'status'      => JsysAsiLoginHistoryStatus::SUCCESS,
                'tfa_status'  => JsysAsiLoginHistoryTfaStatus::DISABLED,
                'lock_status' => JsysAsiLoginHistoryLockStatus::DISABLED,
                'ua'          => $this->faker->userAgent,
            ],
            [
                'date'        => new \DateTime('2021-05-01 10:30:10'),
                'id'          => 'test1',
                'ip'          => '172.16.2.37',
                'status'      => JsysAsiLoginHistoryStatus::DENY,
                'tfa_status'  => JsysAsiLoginHistoryTfaStatus::INVALID,
                'lock_status' => JsysAsiLoginHistoryLockStatus::UNLOCKED,
                'ua'          => $this->faker->userAgent,
            ],
            [
                'date'        => new \DateTime('2021-05-02 05:40:20'),
                'id'          => 'test2',
                'ip'          => '61.125.8.91',
                'status'      => JsysAsiLoginHistoryStatus::FAILURE,
                'tfa_status'  => JsysAsiLoginHistoryTfaStatus::VALID,
                'lock_status' => JsysAsiLoginHistoryLockStatus::UNLOCKED,
                'ua'          => $this->faker->userAgent,
            ],
            [
                'date'        => new \DateTime('2021-05-03 13:10:30'),
                'id'          => 'test2',
                'ip'          => '61.125.8.91',
                'status'      => JsysAsiLoginHistoryStatus::FAILURE,
                'tfa_status'  => JsysAsiLoginHistoryTfaStatus::INVALID,
                'lock_status' => JsysAsiLoginHistoryLockStatus::UNLOCKED,
                'ua'          => $this->faker->userAgent,
            ],
            [
                'date'        => new \DateTime('2021-05-03 13:15:40'),
                'id'          => 'test11',
                'ip'          => '50.30.200.2',
                'status'      => JsysAsiLoginHistoryStatus::DENY,
                'tfa_status'  => JsysAsiLoginHistoryTfaStatus::USED,
                'lock_status' => JsysAsiLoginHistoryLockStatus::LOCKED,
                'ua'          => $this->faker->userAgent,
            ],
            [
                'date'        => new \DateTime('2021-05-04 00:00:50'),
                'id'          => 'test2',
                'ip'          => '192.168.100.10',
                'status'      => JsysAsiLoginHistoryStatus::SUCCESS,
                'tfa_status'  => JsysAsiLoginHistoryTfaStatus::VALID,
                'lock_status' => JsysAsiLoginHistoryLockStatus::UNLOCKED,
                'ua'          => $this->faker->userAgent,
            ],
            [
                'date'        => new \DateTime('2021-05-04 00:01:00'),
                'id'          => 'test2',
                'ip'          => '61.125.8.91',
                'status'      => JsysAsiLoginHistoryStatus::DENY,
                'tfa_status'  => JsysAsiLoginHistoryTfaStatus::USED,
                'lock_status' => JsysAsiLoginHistoryLockStatus::UNLOCKED,
                'ua'          => $this->faker->userAgent,
            ],
            [
                'date'        => new \DateTime('2021-05-04 00:01:10'),
                'id'          => 'test2',
                'ip'          => '61.125.8.91',
                'status'      => JsysAsiLoginHistoryStatus::DENY,
                'tfa_status'  => JsysAsiLoginHistoryTfaStatus::VALID,
                'lock_status' => JsysAsiLoginHistoryLockStatus::LOCKED,
                'ua'          => $this->faker->userAgent,
            ],
            [
                'date'        => new \DateTime('2021-05-05 00:00:00'),
                'id'          => 'test1',
                'ip'          => '172.16.2.37',
                'status'      => JsysAsiLoginHistoryStatus::SUCCESS,
                'tfa_status'  => JsysAsiLoginHistoryTfaStatus::VALID,
                'lock_status' => JsysAsiLoginHistoryLockStatus::UNLOCKED,
                'ua'          => $this->faker->userAgent,
            ],
            [
                'date'        => new \DateTime('2021-05-08 00:00:00'),
                'id'          => 'test11',
                'ip'          => '50.30.200.2',
                'status'      => JsysAsiLoginHistoryStatus::DENY,
                'tfa_status'  => JsysAsiLoginHistoryTfaStatus::INVALID,
                'lock_status' => JsysAsiLoginHistoryLockStatus::LOCKED,
                'ua'          => $this->faker->userAgent,
            ],
            [
                'date'        => new \DateTime('2021-05-11 00:00:00'),
                'id'          => 'test2',
                'ip'          => '192.168.100.10',
                'status'      => JsysAsiLoginHistoryStatus::SUCCESS,
                'tfa_status'  => JsysAsiLoginHistoryTfaStatus::DISABLED,
                'lock_status' => JsysAsiLoginHistoryLockStatus::UNLOCKED,
                'ua'          => $this->faker->userAgent,
            ],
        ];
    }

    /**
     * 履歴データの指定列のHTML文字列を全て取得します。
     * @param Crawler $crawler
     * @param int $num
     * @return array
     */
    private function getHistoryColumnValues(Crawler $crawler, $num)
    {
        return $crawler
            ->filter('[id^="jsys_asi_history_data_"] td:nth-child(' . $num . ')')
            ->each(function (Crawler $node, $i) { return $node->html(); });
    }

}
