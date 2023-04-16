<?php
namespace Plugin\JsysAsi\Service;

class JsysAsiCryptService
{
    /**
     * ハッシュアルゴリズム
     * @var string
     */
    const ALGO = 'sha256';

    /**
     * 暗号化方式
     * @var string
     */
    const METHOD = 'aes-256-ctr';

    /**
     * パスワードのバイト数
     * @var int
     */
    const LEN_PASSWORD = 16;

    /**
     * ソルトのバイト数
     * @var int
     */
    const LEN_SALT = 16;


    /**
     * JsysAsiCrypt constructor.
     */
    public function __construct()
    {
        // If there is an initial setting, please describe it here.
    }

    /**
     * パスワードを生成します。
     * @return string
     */
    public function createPassword()
    {
        return bin2hex(openssl_random_pseudo_bytes(self::LEN_PASSWORD));
    }

    /**
     * ソルトを生成します。
     * @return string
     */
    public function createSalt()
    {
        return bin2hex(openssl_random_pseudo_bytes(self::LEN_SALT));
    }

    /**
     * 暗号化を行います。
     * @param string $data
     * @param string $password
     * @param string $salt
     * @throws \Exception
     * @return boolean|string
     */
    public function encrypt($data, $password, $salt)
    {
        // データ・パスワード・ソルトが空の場合はエラー
        if (!$data) {
            throw new \Exception(trans(
                'jsys_asi.service.crypt_service.blank.data'
            ));
        }
        if (!$password) {
            throw new \Exception(trans(
                'jsys_asi.service.crypt_service.blank.password'
            ));
        }
        if (!$salt) {
            throw new \Exception(trans(
                'jsys_asi.service.crypt_service.blank.salt'
            ));
        }

        // 初期ベクトル、キーを生成
        $length = openssl_cipher_iv_length(self::METHOD);
        $iv     = openssl_random_pseudo_bytes($length);
        $key    = hash_hkdf(self::ALGO, $password, 0, '', $salt);

        // 暗号化
        $encrypted = openssl_encrypt($data, self::METHOD, $key, 0, $iv);
        if (false === $encrypted) {
            return false;
        }

        // 初期ベクトルを結合して返す
        return base64_encode($iv . $encrypted);
    }

    /**
     * 復号化を行います。
     * @param string $data
     * @param string $password
     * @param string $salt
     * @throws \Exception
     * @return boolean|string
     */
    public function decrypt($data, $password, $salt)
    {
        // データ・パスワード・ソルトが空の場合はエラー
        if (!$data) {
            throw new \Exception(trans(
                'jsys_asi.service.crypt_service.blank.data'
            ));
        }
        if (!$password) {
            throw new \Exception(trans(
                'jsys_asi.service.crypt_service.blank.password'
            ));
        }
        if (!$salt) {
            throw new \Exception(trans(
                'jsys_asi.service.crypt_service.blank.salt'
            ));
        }

        // 初期ベクトルと暗号化データを取得し、キーを生成
        $data      = base64_decode($data);
        $length    = openssl_cipher_iv_length(self::METHOD);
        $iv        = substr($data, 0, $length);
        $encrypted = substr($data, $length);
        $key       = hash_hkdf(self::ALGO, $password, 0, '', $salt);

        // 復号して返す
        return openssl_decrypt($encrypted, self::METHOD, $key, 0, $iv);
    }

}
