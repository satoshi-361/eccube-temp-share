<?php

namespace Plugin\EccubePaymentLite4\Service;

use Eccube\Common\EccubeConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;

class GmoEpsilonRequestService
{
    /**
     * @var eccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var Config
     */
    protected $Config;

    public function __construct(
        EccubeConfig $eccubeConfig,
        ConfigRepository $configRepository
    ) {
        $this->eccubeConfig = $eccubeConfig;

        $this->Config = $configRepository->get();
    }

    /**
     * リクエストを送信
     *
     * @return array or boolean
     */
    public function sendData($url, $arrParameter, $version = null)
    {
        //CGIバージョンが1の場合は、パラメータの文字コードをUTF8⇒EUCJPに変換
        if (isset($arrParameter['version']) && $arrParameter['version'] === '1') {
            mb_convert_variables('SJIS-win', 'UTF-8', $arrParameter);
        }
        $client = new Client();
        try {
            $response = $client->post($url, [
                            'form_params' => $arrParameter,
                        ]);
        } catch (\RuntimeException $e) {
            logs('gmo_epsilon')->info('CurlException. url='.$url.' parameter='.print_r($arrParameter, true));

            return $e->getErrorNo();
        } catch (BadResponseException $e) {
            logs('gmo_epsilon')->info('BadResponseException. url='.$url.' parameter='.print_r($arrParameter, true));

            return false;
        } catch (\Exception $e) {
            logs('gmo_epsilon')->info('Exception. url='.$url.' parameter='.print_r($arrParameter, true));

            return false;
        }

        $response = $response->getBody(true);

        if (is_null($response)) {
            // $msg = 'レスポンスデータエラー: レスポンスがありません。';
            return false;
        }

        // Shift-JISをUNICODEに変換する
        $response = str_replace('x-sjis-cp932', 'UTF-8', $response);

        // XMLパーサを生成する。
        $parser = xml_parser_create('utf-8');

        // 空白文字は読み飛ばしてXMLを読み取る
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);

        // 配列にXMLのデータを格納する
        $arrVal = [];
        $err = xml_parse_into_struct($parser, $response, $arrVal, $idx);

        // 開放する
        xml_parser_free($parser);

        return $arrVal;
    }

    /**
     * XMLのタグを指定し、要素を取得
     *
     * @param array $arrVal
     * @param string $tag
     * @param string $att
     *
     * @return string
     */
    public function getXMLValue($arrVal, $tag, $att)
    {
        $ret = '';
        foreach ((array) $arrVal as $array) {
            if ($tag == $array['tag']) {
                if (!is_array($array['attributes'])) {
                    continue;
                }
                foreach ($array['attributes'] as $key => $val) {
                    if ($key == $att) {
                        $ret = mb_convert_encoding(urldecode($val), 'UTF-8', 'SJIS');
                        break;
                    }
                }
            }
        }

        return $ret;
    }
}
