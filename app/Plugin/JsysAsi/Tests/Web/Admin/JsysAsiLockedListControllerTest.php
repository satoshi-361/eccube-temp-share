<?php
namespace Plugin\JsysAsi\Tests\Web\Admin;

use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\JsysAsi\Entity\JsysAsiLockedIpAddress;

class JsysAsiLockedListControllerTest extends AbstractAdminWebTestCase
{

    /**
     * Setup method.
     * {@inheritDoc}
     * @see \Eccube\Tests\Web\Admin\AbstractAdminWebTestCase::setUp()
     */
    public function setUp()
    {
        parent::setUp();

        $this->deleteAllRows(['plg_jsys_asi_locked_ip_address']);

        $this->createRecords();
    }

    /**
     * ルーティングテスト
     */
    public function testRouting()
    {
        // ロック済み一覧にアクセスできるか
        $this->client->request('GET', $this->generateUrl('admin_jsys_asi_locked_list'));
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    /**
     * 初期表示テスト
     */
    public function testIndex()
    {
        // ロック済み一覧にアクセスできるか
        $crawler = $this->client->request('GET', $this->generateUrl(
            'admin_jsys_asi_locked_list'
        ));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 検索結果の件数が作成したレコード数と一致するか
        $this->assertContains(
            '検索結果：11件が該当しました',
            $crawler->filter('#search_form #search_total_count')->text()
        );
    }

    /**
     * IPアドレス検索テスト
     */
    public function testSearchWithIpAddress()
    {
        // IPアドレスで抽出
        $ip      = '61.172.16.5';
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_jsys_asi_locked_list'),
            [
                'jsys_asi_admin_locked_list_search' => [
                    '_token'     => 'dummy',
                    'ip_address' => $ip,
                ],
            ]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 検索結果の件数が予想と一致するか
        $this->assertContains(
            '検索結果：1件が該当しました',
            $crawler->filter('#search_form #search_total_count')->text()
        );

        // 抽出されたレコードのIPアドレスを確認
        $td = $crawler->filter('table tbody tr')->first()->filter('td')->first();
        $this->assertSame($ip, $td->text());
    }

    /**
     * レコードなしテスト
     */
    public function testNoRecord()
    {
        // 未登録のIPアドレスで抽出
        $ip      = '192.168.1.101';
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_jsys_asi_locked_list'),
            [
                'jsys_asi_admin_locked_list_search' => [
                    '_token'     => 'dummy',
                    'ip_address' => $ip,
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
        // 検索エラーになるようなデータを作成
        $max = $this->eccubeConfig['eccube_stext_len'];
        $ip  = str_pad('1', $max + 1, '1');

        // 作成したデータを使って抽出
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('admin_jsys_asi_locked_list'),
            [
                'jsys_asi_admin_locked_list_search' => [
                    '_token'     => 'dummy',
                    'ip_address' => $ip,
                ],
            ]
        );
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        // 検証失敗メッセージが表示されているか
        $sele = '#jsys_asi_admin_locked_list_search_ip_address + '
              . 'span.invalid-feedback .form-error-message';
        $expected = "値が長すぎます。{$max}文字以内でなければなりません。";
        $this->assertSame($expected, $crawler->filter($sele)->text());

        // エラー時のメッセージが表示されているか
        $this->assertContains('検索条件に誤りがあります', $crawler->html());
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
            $this->generateUrl('admin_jsys_asi_locked_list_page', ['page_no' => 2]),
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
        // ページリンクがページ2か
        $this->expected = 2;
        $this->actual   = intval($paging->text());
        $this->verify();
    }


    /**
     * ロック済みIPアドレスを作成します。
     */
    private function createRecords()
    {
        // 61.172.16.1～61.172.16.11の11件作成
        for ($i = 1; $i <= 11; ++$i) {
            $lockedIp = new JsysAsiLockedIpAddress();
            $lockedIp->setIpAddress("61.172.16.{$i}");
            $this->entityManager->persist($lockedIp);
            $this->entityManager->flush($lockedIp);
        }
    }

}
