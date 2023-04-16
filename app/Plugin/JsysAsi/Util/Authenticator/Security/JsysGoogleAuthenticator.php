<?php

declare(strict_types=1);

namespace Plugin\JsysAsi\Util\Authenticator\Security;

use ParagonIE\ConstantTime\Base32;
use Plugin\JsysAsi\Util\Authenticator\Model\JsysTwoFactorInterface;

class JsysGoogleAuthenticator implements JsysGoogleAuthenticatorInterface
{
    /**
     * @var JsysGoogleTotpFactory
     */
    private $totpFactory;

    /**
     * @var int
     */
    private $window;

    public function __construct(JsysGoogleTotpFactory $totpFactory, int $window)
    {
        $this->totpFactory = $totpFactory;
        $this->window = $window;
    }

    public function checkCode(JsysTwoFactorInterface $user, string $code): bool
    {
        $code = str_replace(' ', '', $code);
        return $this->totpFactory
            ->createTotpForUser($user)
            ->verify($code, null, $this->window);
    }

    public function getQRContent(JsysTwoFactorInterface $user): string
    {
        return $this->totpFactory->createTotpForUser($user)->getProvisioningUri();
    }

    public function generateSecret(): string
    {
        return Base32::encodeUpperUnpadded(random_bytes(32));
    }

}
