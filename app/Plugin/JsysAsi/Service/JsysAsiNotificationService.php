<?php
namespace Plugin\JsysAsi\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Member;
use Eccube\Repository\BaseInfoRepository;
use Plugin\JsysAsi\Util\JsysAsiUserAgentUtil;

/**
 * 通知サービス
 * @author manabe
 *
 */
class JsysAsiNotificationService
{
    /**
     * ログイン成功通知メールテンプレート
     * @var string
     */
    const TWIG_MAIL_LOGIN_SUCCESS = '@JsysAsi/default/Mail/admin_login_success.twig';

    /**
     * ログイン失敗通知メールテンプレート
     * @var string
     */
    const TWIG_MAIL_LOGIN_FAILURE = '@JsysAsi/default/Mail/admin_login_failure.twig';


    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var BaseInfo
     */
    private $BaseInfo;


    /**
     * JsysAsiNotificationService constructor.
     * @param \Swift_Mailer $mailer
     * @param \Twig_Environment $twig
     * @param RequestStack $requestStack
     * @param BaseInfoRepository $baseInfoRepo
     */
    public function __construct(
        \Swift_Mailer $mailer,
        \Twig_Environment $twig,
        RequestStack $requestStack,
        BaseInfoRepository $baseInfoRepo
    ) {
        $this->mailer       = $mailer;
        $this->twig         = $twig;
        $this->requestStack = $requestStack;
        $this->BaseInfo     = $baseInfoRepo->get();
    }

    /**
     * ログイン成功メールを送信します。
     * @param Member $Member
     * @return number
     */
    public function sendLoginSuccessMail(Member $Member)
    {
        log_info('JsysAsi 管理 ログイン成功メール 送信開始', [
            'Member' => $Member->getName(),
        ]);

        $subject = trans('jsys_asi.mail.admin_login.success.subject', [
            '%shop%'   => $this->BaseInfo->getShopName(),
            '%member%' => $Member->getName(),
        ]);

        $body = $this->twig->render(self::TWIG_MAIL_LOGIN_SUCCESS, [
            'shop_name'  => $this->BaseInfo->getShopName(),
            'login_id'   => $Member->getLoginId(),
            'member'     => $Member->getName(),
            'login_date' => $Member->getLoginDate(),
            'ip'         => $this->requestStack->getMasterRequest()->getClientIp(),
            'ua'         => JsysAsiUserAgentUtil::getUserAgent($this->requestStack),
        ]);

        $message = new \Swift_Message();
        $message
            ->setSubject($subject)
            ->setFrom($this->BaseInfo->getEmail01())
            ->setTo($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04())
            ->setBody($body);

        $count = $this->mailer->send($message);

        log_info('JsysAsi 管理 ログイン成功メール 送信完了', [
            'Member' => $Member->getName(),
        ]);

        return $count;
    }

    /**
     * ログイン失敗メールを送信します。
     * @param string $loginId
     * @return number
     */
    public function sendLoginFailureMail($loginId)
    {
        log_info('JsysAsi 管理 ログイン失敗メール 送信開始', ['loginId' => $loginId]);

        $subject = trans('jsys_asi.mail.admin_login.failure.subject', [
            '%shop%'   => $this->BaseInfo->getShopName(),
        ]);

        $body = $this->twig->render(self::TWIG_MAIL_LOGIN_FAILURE, [
            'shop_name'  => $this->BaseInfo->getShopName(),
            'login_id'   => $loginId,
            'login_date' => new \DateTime(),
            'ip'         => $this->requestStack->getMasterRequest()->getClientIp(),
            'ua'         => JsysAsiUserAgentUtil::getUserAgent($this->requestStack),
        ]);

        $message = new \Swift_Message();
        $message
            ->setSubject($subject)
            ->setFrom($this->BaseInfo->getEmail01())
            ->setTo($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04())
            ->setBody($body);

        $count = $this->mailer->send($message);

        log_info('JsysAsi 管理 ログイン失敗メール 送信完了', ['loginId' => $loginId]);

        return $count;
    }

}
