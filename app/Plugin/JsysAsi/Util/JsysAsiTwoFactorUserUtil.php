<?php
namespace Plugin\JsysAsi\Util;

use Plugin\JsysAsi\Entity\JsysAsiTfaUser;
use Plugin\JsysAsi\Service\JsysAsiCryptService;
use Plugin\JsysAsi\Util\Authenticator\Model\JsysTwoFactorInterface;

/**
 * 2要素認証ユーザーユーティリティ
 * @author manabe
 *
 */
class JsysAsiTwoFactorUserUtil implements JsysTwoFactorInterface
{
    /**
     * @var JsysAsiCryptService
     */
    protected $jsysAsiCryptService;

    /**
     * @var JsysAsiTfaUser
     */
    protected $jsysAsiTfaUser;

    /**
     * @var string
     */
    protected $user_name;


    /**
     * JsysAsiTwoFactorUserUtil constructor.
     * @param JsysAsiCryptService $jsysAsiCryptService
     * @param JsysAsiTfaUser $jsysAsiTfaUser
     * @param string $userName
     */
    public function __construct(
        JsysAsiCryptService $jsysAsiCryptService,
        JsysAsiTfaUser $jsysAsiTfaUser,
        string $userName
    ) {
        $this->jsysAsiCryptService = $jsysAsiCryptService;
        $this->jsysAsiTfaUser      = $jsysAsiTfaUser;
        $this->user_name           = $userName;
    }

    /**
     * {@inheritDoc}
     * @see \Plugin\JsysAsi\Util\Authenticator\Model\JsysTwoFactorInterface::isAuthenticatorEnabled()
     * @return boolean
     */
    public function isAuthenticatorEnabled(): bool
    {
        return $this->jsysAsiTfaUser->getEnabled() && $this->jsysAsiTfaUser->getSecret();
    }

    /**
     * {@inheritDoc}
     * @see \Plugin\JsysAsi\Util\Authenticator\Model\JsysTwoFactorInterface::getAuthenticatorUsername()
     */
    public function getAuthenticatorUsername(): string
    {
        if (!$this->jsysAsiTfaUser->getMemberId()) {
            throw new \Exception('Please set a member_id.');
        }
        if (!$this->user_name) {
            throw new \Exception('Please set a user_name.');
        }
        return $this->user_name;
    }

    /**
     * {@inheritDoc}
     * @see \Plugin\JsysAsi\Util\Authenticator\Model\JsysTwoFactorInterface::getAuthenticatorSecret()
     */
    public function getAuthenticatorSecret(): ?string
    {
        if (!$this->jsysAsiTfaUser->getSecret()) {
            throw new \Exception('Please set a secret.');
        }
        if (!$this->jsysAsiTfaUser->getSecretPassword()) {
            throw new \Exception('Please set a password.');
        }
        if (!$this->jsysAsiTfaUser->getSecretSalt()) {
            throw new \Exception('Please set a salt.');
        }

        return $this->jsysAsiCryptService->decrypt(
            $this->jsysAsiTfaUser->getSecret(),
            $this->jsysAsiTfaUser->getSecretPassword(),
            $this->jsysAsiTfaUser->getSecretSalt()
        );
    }

}
