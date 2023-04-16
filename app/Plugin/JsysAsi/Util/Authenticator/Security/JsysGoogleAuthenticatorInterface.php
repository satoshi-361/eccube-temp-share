<?php

declare(strict_types=1);

namespace Plugin\JsysAsi\Util\Authenticator\Security;

use Plugin\JsysAsi\Util\Authenticator\Model\JsysTwoFactorInterface;

interface JsysGoogleAuthenticatorInterface
{
    /**
     * ユーザーが入力したコードを検証します。
     */
    public function checkCode(JsysTwoFactorInterface $user, string $code): bool;

    /**
     * Google Authenticatorで読み取るQRコードのコンテンツを取得します。
     */
    public function getQRContent(JsysTwoFactorInterface $user): string;

    /**
     * Google Authenticatorの新しい秘密鍵を生成します。
     */
    public function generateSecret(): string;

}
