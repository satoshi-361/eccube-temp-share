<?php

namespace Plugin\PayPalCheckout\Util;

/**
 * 文字列に関するユーティル
 *
 * @package Plugin\PayPalCheckout\Util
 */
class StringUtil {

    /**
     * 暗号的に安全なランダム文字列を最大200桁まで生成する
     *
     * @param int $length
     * @return string
     * @throws \Exception
     */
    public static function createRandomString(int $length = 200): string
    {
        $randomString = bin2hex(random_bytes(100));
        return substr($randomString, 0, $length);
    }

    /**
     * 与えられた文字列をハッシュ化して返す
     * ・同じ文字列を与えた場合、同じハッシュ値が返る
     * ・先頭7文字は毎回同じになるので、それ以上の十分な長さの $length が良い
     *
     * @param string $text
     * @param int $length
     * @return string
     */
    public static function generateHashString(string $text, int $length = 200): string
    {
        // CRYPT_BLOWFISH 形式のソルトを指定する
        $hashString = crypt($text, '$2y$15$PsMso54sl0stA9qJf7Bg3Z$');
        return substr($hashString, 0, $length);
    }
}
