<?php

declare(strict_types=1);

namespace Plugin\JsysAsi\Util\Authenticator\Security;

use OTPHP\TOTP;
use OTPHP\TOTPInterface;
use Plugin\JsysAsi\Util\Authenticator\Model\JsysTwoFactorInterface;

class JsysGoogleTotpFactory
{
    /**
     * @var string|null
     */
    private $server;

    /**
     * @var string|null
     */
    private $issuer;

    /**
     * @var int
     */
    private $digits;

    public function __construct(?string $server, ?string $issuer, int $digits)
    {
        $this->server = $server;
        $this->issuer = $issuer;
        $this->digits = $digits;
    }

    public function createTotpForUser(JsysTwoFactorInterface $user): TOTPInterface
    {
        $totp = TOTP::create($user->getAuthenticatorSecret(), 30, 'sha1', $this->digits);

        $userAndHost = $user->getAuthenticatorUsername()
                     . ($this->server ? '@' . $this->server : '');
        $totp->setLabel($userAndHost);

        if ($this->issuer) {
            $totp->setIssuer($this->issuer);
        }

        return $totp;
    }

}
