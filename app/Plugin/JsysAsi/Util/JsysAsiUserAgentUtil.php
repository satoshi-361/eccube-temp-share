<?php
namespace Plugin\JsysAsi\Util;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * ユーザーエージェントユーティリティ
 * @author manabe
 *
 */
class JsysAsiUserAgentUtil
{
    /**
     * User-Agent Client Hint または User-Agent を取得します。
     * @param RequestStack $requestStack
     * @return string
     */
    public static function getUserAgent(RequestStack $requestStack)
    {
        if (is_null($requestStack->getMasterRequest())) {
            return '';
        }
        if (empty($requestStack->getMasterRequest()->headers)) {
            return '';
        }

        $headers = $requestStack->getMasterRequest()->headers;
        if ($headers->get('sec-ch-ua')) {
            return $headers->get('sec-ch-ua');
        }
        if ($headers->get('User-Agent')) {
            return $headers->get('User-Agent');
        }
        return '';
    }

}
