<?php

namespace Plugin\JsysAsi\Entity;

use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Plugin\JsysAsi\Entity\Config', false)) {
    /**
     * Config
     *
     * @ORM\Table(name="plg_jsys_asi_config")
     * @ORM\Entity(repositoryClass="Plugin\JsysAsi\Repository\ConfigRepository")
     */
    class Config
    {
        /**
         * ID
         * @var int
         *
         * @ORM\Column(name="id", type="integer", options={"unsigned":true})
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        private $id;

        /**
         * 2要素認証
         * @var boolean
         *
         * @ORM\Column(name="option_tfa", type="boolean", options={
         *   "default":false
         * })
         */
        private $option_tfa;

        /**
         * 2要素認証マスターキー
         * @var string
         *
         * @ORM\Column(
         *   name="option_tfa_master_key",
         *   type="string",
         *   length=255,
         *   nullable=true
         * )
         */
        private $option_tfa_master_key;

        /**
         * 2要素認証マスターキーパスワード
         * @var string
         *
         * @ORM\Column(
         *   name="option_tfa_master_key_password",
         *   type="string",
         *   length=255,
         *   nullable=true
         * )
         */
        private $option_tfa_master_key_password;

        /**
         * 2要素認証マスターキーSalt
         * @var string
         *
         * @ORM\Column(
         *   name="option_tfa_master_key_salt",
         *   type="string",
         *   length=255,
         *   nullable=true
         * )
         */
        private $option_tfa_master_key_salt;

        /**
         * ログイン成功メール
         * @var boolean
         *
         * @ORM\Column(
         *   name="option_login_success_mail",
         *   type="boolean",
         *   options={"default":false}
         * )
         */
        private $option_login_success_mail;

        /**
         * ログイン失敗メール
         * @var boolean
         *
         * @ORM\Column(
         *   name="option_login_failure_mail",
         *   type="boolean",
         *   options={"default":false}
         * )
         */
        private $option_login_failure_mail;

        /**
         * IPアドレスロック
         * @var boolean
         *
         * @ORM\Column(
         *   name="option_ip_address_lock",
         *   type="boolean",
         *   options={"default":false}
         * )
         */
        private $option_ip_address_lock;

        /**
         * IPアドレスロックまでの回数
         * @var int
         *
         * @ORM\Column(
         *   name="option_ip_address_lock_count",
         *   type="integer",
         *   nullable=true,
         *   options={"unsigned":true}
         * )
         */
        private $option_ip_address_lock_count;

        /**
         * 登録日
         * @var \DateTime
         *
         * @ORM\Column(name="create_date", type="datetimetz")
         */
        private $create_date;

        /**
         * 更新日
         * @var \DateTime
         *
         * @ORM\Column(name="update_date", type="datetimetz")
         */
        private $update_date;


        /**
         * IDを取得します。
         * @return int
         */
        public function getId()
        {
            return $this->id;
        }

        /**
         * 2要素認証を設定します。
         * @param boolean $optionTfa
         * @return \Plugin\JsysAsi\Entity\Config
         */
        public function setOptionTfa($optionTfa)
        {
            $this->option_tfa = $optionTfa;
            return $this;
        }

        /**
         * 2要素認証を取得します。
         * @return boolean
         */
        public function getOptionTfa()
        {
            return $this->option_tfa;
        }

        /**
         * 2要素認証マスターキーを設定します。
         * @param string $optionTfaMasterKey
         * @return \Plugin\JsysAsi\Entity\Config
         */
        public function setOptionTfaMasterKey($optionTfaMasterKey)
        {
            $this->option_tfa_master_key = $optionTfaMasterKey;
            return $this;
        }

        /**
         * 2要素認証マスターキーを取得します。
         * @return string
         */
        public function getOptionTfaMasterKey()
        {
            return $this->option_tfa_master_key;
        }

        /**
         * 2要素認証マスターキーパスワードを設定します。
         * @param string $optionTfaMasterKeyPassword
         * @return \Plugin\JsysAsi\Entity\Config
         */
        public function setOptionTfaMasterKeyPassword($optionTfaMasterKeyPassword)
        {
            $this->option_tfa_master_key_password = $optionTfaMasterKeyPassword;
            return $this;
        }

        /**
         * 2要素認証マスターキーパスワードを取得します。
         * @return string
         */
        public function getOptionTfaMasterKeyPassword()
        {
            return $this->option_tfa_master_key_password;
        }

        /**
         * 2要素認証マスターキーSaltを設定します。
         * @param string $optionTfaMasterKeySalt
         * @return \Plugin\JsysAsi\Entity\Config
         */
        public function setOptionTfaMasterKeySalt($optionTfaMasterKeySalt)
        {
            $this->option_tfa_master_key_salt = $optionTfaMasterKeySalt;
            return $this;
        }

        /**
         * 2要素認証マスターキーSaltを取得します。
         * @return string
         */
        public function getOptionTfaMasterKeySalt()
        {
            return $this->option_tfa_master_key_salt;
        }

        /**
         * ログイン成功メールを設定します。
         * @param boolean $optionLoginSuccessMail
         * @return \Plugin\JsysAsi\Entity\Config
         */
        public function setOptionLoginSuccessMail($optionLoginSuccessMail)
        {
            $this->option_login_success_mail = $optionLoginSuccessMail;
            return $this;
        }

        /**
         * ログイン成功メールを取得します。
         * @return boolean
         */
        public function getOptionLoginSuccessMail()
        {
            return $this->option_login_success_mail;
        }

        /**
         * ログイン失敗メールを設定します。
         * @param boolean $optionLoginFailureMail
         * @return \Plugin\JsysAsi\Entity\Config
         */
        public function setOptionLoginFailureMail($optionLoginFailureMail)
        {
            $this->option_login_failure_mail = $optionLoginFailureMail;
            return $this;
        }

        /**
         * ログイン失敗メールを取得します。
         * @return boolean
         */
        public function getOptionLoginFailureMail()
        {
            return $this->option_login_failure_mail;
        }

        /**
         * IPアドレスロックを設定します。
         * @param boolean $optionIpAddressLock
         * @return \Plugin\JsysAsi\Entity\Config
         */
        public function setOptionIpAddressLock($optionIpAddressLock)
        {
            $this->option_ip_address_lock = $optionIpAddressLock;
            return $this;
        }

        /**
         * IPアドレスロックを取得します。
         * @return boolean
         */
        public function getOptionIpAddressLock()
        {
            return $this->option_ip_address_lock;
        }

        /**
         * IPアドレスロックまでの回数を設定します。
         * @param int $optionIpAddressLockCount
         * @return \Plugin\JsysAsi\Entity\Config
         */
        public function setOptionIpAddressLockCount($optionIpAddressLockCount)
        {
            $this->option_ip_address_lock_count = $optionIpAddressLockCount;
            return $this;
        }

        /**
         * IPアドレスロックまでの回数を取得します。
         * @return int
         */
        public function getOptionIpAddressLockCount()
        {
            return $this->option_ip_address_lock_count;
        }

        /**
         * 登録日を設定します。
         * @param \DateTime $createDate
         * @return \Plugin\JsysAsi\Entity\Config
         */
        public function setCreateDate($createDate)
        {
            $this->create_date = $createDate;
            return $this;
        }

        /**
         * 登録日を取得します。
         * @return \DateTime
         */
        public function getCreateDate()
        {
            return $this->create_date;
        }

        /**
         * 更新日を設定します。
         * @param \DateTime $updateDate
         * @return \Plugin\JsysAsi\Entity\Config
         */
        public function setUpdateDate($updateDate)
        {
            $this->update_date = $updateDate;
            return $this;
        }

        /**
         * 更新日を取得します。
         * @return \DateTime
         */
        public function getUpdateDate()
        {
            return $this->update_date;
        }

    }
}
