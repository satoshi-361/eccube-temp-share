<?php

namespace Plugin\PayPalCheckout\Tests\Service;

use Eccube\Entity\Master\Pref;
use Eccube\Repository\Master\PrefRepository;
use Eccube\Tests\Service\AbstractServiceTestCase;
use Plugin\PayPalCheckout\Service\PayPalOrderService;

/**
 * Class PayPalOrderServiceTest
 * @package Plugin\PayPalCheckout\Tests\Service
 */
class PayPalOrderServiceTest extends AbstractServiceTestCase
{
    /**
     * @var PayPalOrderService
     */
    private $service;

    /**
     * @var PrefRepository
     */
    private $prefRepository;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->service = self::$container->get(PayPalOrderService::class);
        $this->prefRepository = self::$container->get(PrefRepository::class);
    }

    /**
     * @test
     */
    public function instance()
    {
        $this->assertInstanceOf(PayPalOrderService::class, $this->service);
    }

    /**
     * @test
     * @dataProvider getPrefList
     *
     * @param string $expected
     * @param string $prefString
     */
    public function getPrefByPayPalPref(string $expected, string $prefString)
    {
        /** @var string $index */
        $index = $this->service->getPrefByPayPalPref($prefString);

        /** @var Pref $pref */
        $pref = $this->prefRepository->findOneBy([
            'name' => $index
        ]);
        $this->assertEquals($expected, $pref->getName());
    }

    /**
     * @return array
     */
    public function getPrefList(): array
    {
        return [
            "北海道" => ["北海道", "北海道"],
            "青森県" => ["青森県", "青森県"],
            "岩手県" => ["岩手県", "岩手県"],
            "宮城県" => ["宮城県", "宮城県"],
            "秋田県" => ["秋田県", "秋田県"],
            "山形県" => ["山形県", "山形県"],
            "福島県" => ["福島県", "福島県"],
            "茨城県" => ["茨城県", "茨城県"],
            "栃木県" => ["栃木県", "栃木県"],
            "群馬県" => ["群馬県", "群馬県"],
            "埼玉県" => ["埼玉県", "埼玉県"],
            "千葉県" => ["千葉県", "千葉県"],
            "東京都" => ["東京都", "東京都"],
            "神奈川県" => ["神奈川県", "神奈川県"],
            "新潟県" => ["新潟県", "新潟県"],
            "富山県" => ["富山県", "富山県"],
            "石川県" => ["石川県", "石川県"],
            "福井県" => ["福井県", "福井県"],
            "山梨県" => ["山梨県", "山梨県"],
            "長野県" => ["長野県", "長野県"],
            "岐阜県" => ["岐阜県", "岐阜県"],
            "静岡県" => ["静岡県", "静岡県"],
            "愛知県" => ["愛知県", "愛知県"],
            "三重県" => ["三重県", "三重県"],
            "滋賀県" => ["滋賀県", "滋賀県"],
            "京都府" => ["京都府", "京都府"],
            "大阪府" => ["大阪府", "大阪府"],
            "兵庫県" => ["兵庫県", "兵庫県"],
            "奈良県" => ["奈良県", "奈良県"],
            "和歌山県" => ["和歌山県", "和歌山県"],
            "鳥取県" => ["鳥取県", "鳥取県"],
            "島根県" => ["島根県", "島根県"],
            "岡山県" => ["岡山県", "岡山県"],
            "広島県" => ["広島県", "広島県"],
            "山口県" => ["山口県", "山口県"],
            "徳島県" => ["徳島県", "徳島県"],
            "香川県" => ["香川県", "香川県"],
            "愛媛県" => ["愛媛県", "愛媛県"],
            "高知県" => ["高知県", "高知県"],
            "福岡県" => ["福岡県", "福岡県"],
            "佐賀県" => ["佐賀県", "佐賀県"],
            "長崎県" => ["長崎県", "長崎県"],
            "熊本県" => ["熊本県", "熊本県"],
            "大分県" => ["大分県", "大分県"],
            "宮崎県" => ["宮崎県", "宮崎県"],
            "鹿児島県" => ["鹿児島県", "鹿児島県"],
            "沖縄県" => ["沖縄県", "沖縄県"],
            "北海道ローマ字" => ["北海道", "hokkaido"],
            "青森県ローマ字" => ["青森県", "aomori"],
            "岩手県ローマ字" => ["岩手県", "iwate"],
            "宮城県ローマ字" => ["宮城県", "miyagi"],
            "秋田県ローマ字" => ["秋田県", "akita"],
            "山形県ローマ字" => ["山形県", "yamagata"],
            "福島県ローマ字" => ["福島県", "fukushima"],
            "茨城県ローマ字" => ["茨城県", "ibaraki"],
            "栃木県ローマ字" => ["栃木県", "tochigi"],
            "群馬県ローマ字" => ["群馬県", "gunma"],
            "埼玉県ローマ字" => ["埼玉県", "saitama"],
            "千葉県ローマ字" => ["千葉県", "chiba"],
            "東京都ローマ字" => ["東京都", "tokyo"],
            "神奈川県ローマ字" => ["神奈川県", "kanagawa"],
            "新潟県ローマ字" => ["新潟県", "niigata"],
            "富山県ローマ字" => ["富山県", "toyama"],
            "石川県ローマ字" => ["石川県", "ishikawa"],
            "福井県ローマ字" => ["福井県", "fukui"],
            "山梨県ローマ字" => ["山梨県", "yamanashi"],
            "長野県ローマ字" => ["長野県", "nagano"],
            "岐阜県ローマ字" => ["岐阜県", "gifu"],
            "静岡県ローマ字" => ["静岡県", "shizuoka"],
            "愛知県ローマ字" => ["愛知県", "aichi"],
            "三重県ローマ字" => ["三重県", "mie"],
            "滋賀県ローマ字" => ["滋賀県", "shiga"],
            "京都府ローマ字" => ["京都府", "kyoto"],
            "大阪府ローマ字" => ["大阪府", "osaka"],
            "兵庫県ローマ字" => ["兵庫県", "hyogo"],
            "奈良県ローマ字" => ["奈良県", "nara"],
            "和歌山県ローマ字" => ["和歌山県", "wakayama"],
            "鳥取県ローマ字" => ["鳥取県", "tottori"],
            "島根県ローマ字" => ["島根県", "shimane"],
            "岡山県ローマ字" => ["岡山県", "okayama"],
            "広島県ローマ字" => ["広島県", "hiroshima"],
            "山口県ローマ字" => ["山口県", "yamaguchi"],
            "徳島県ローマ字" => ["徳島県", "tokushima"],
            "香川県ローマ字" => ["香川県", "kagawa"],
            "愛媛県ローマ字" => ["愛媛県", "ehime"],
            "高知県ローマ字" => ["高知県", "kochi"],
            "福岡県ローマ字" => ["福岡県", "fukuoka"],
            "佐賀県ローマ字" => ["佐賀県", "saga"],
            "長崎県ローマ字" => ["長崎県", "nagasaki"],
            "熊本県ローマ字" => ["熊本県", "kumamoto"],
            "大分県ローマ字" => ["大分県", "oita"],
            "宮崎県ローマ字" => ["宮崎県", "miyazaki"],
            "鹿児島県ローマ字" => ["鹿児島県", "kagoshima"],
            "沖縄県ローマ字" => ["沖縄県", "okinawa"]
        ];
    }
}
