<?php

declare(strict_types=1);

namespace Plugin\JsysAsi\Util\Authenticator\Model;

interface JsysTwoFactorInterface
{
    /**
     * 2要素認証が有効なユーザーであればtrueを取得します。
     */
    public function isAuthenticatorEnabled(): bool;

    /**
     * ユーザー名を取得します。
     */
    public function getAuthenticatorUsername(): string;

    /**
     * コードの生成に必要な秘密鍵を取得します。
     * 空の文字列の場合は認証が無効となります。
     */
    public function getAuthenticatorSecret(): ?string;

}
