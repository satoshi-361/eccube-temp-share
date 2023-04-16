<?php

namespace Plugin\JsysAsi;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Member;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\MemberRepository;
use Plugin\JsysAsi\Entity\Config;
use Plugin\JsysAsi\Entity\JsysAsiLoginHistoryLockStatus;
use Plugin\JsysAsi\Entity\JsysAsiLoginHistoryStatus;
use Plugin\JsysAsi\Entity\JsysAsiLoginHistoryTfaStatus;
use Plugin\JsysAsi\Repository\ConfigRepository;
use Plugin\JsysAsi\Repository\JsysAsiTfaUserRepository;
use Plugin\JsysAsi\Repository\JsysAsiTfaOtpHistoryRepository;
use Plugin\JsysAsi\Repository\JsysAsiLoginHistoryRepository;
use Plugin\JsysAsi\Service\JsysAsiTfaService;
use Plugin\JsysAsi\Service\JsysAsiNotificationService;
use Plugin\JsysAsi\Service\JsysAsiLockService;
use Doctrine\ORM\Query\Expr\Join;
use Eccube\Request\Context;

class Event implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Context
     */
    private $requestContext;

    /**
     * @var JsysAsiTfaService
     */
    private $tfaService;

    /**
     * @var JsysAsiLockService
     */
    private $lockService;

    /**
     * @var JsysAsiNotificationService
     */
    private $notifyService;

    /**
     * @var MemberRepository
     */
    private $memberRepo;

    /**
     * @var JsysAsiTfaUserRepository
     */
    private $tfaUserRepo;

    /**
     * @var JsysAsiTfaOtpHistoryRepository
     */
    private $otpHistoryRepo;

    /**
     * @var JsysAsiLoginHistoryRepository
     */
    private $loginHistoryRepo;

    /**
     * @var Config
     */
    private $Config;


    /**
     * JsysAsi Event constructor.
     * @param EntityManagerInterface $entityManager
     * @param \Twig_Environment $twig
     * @param RequestStack $requestStack
     * @param Context $requestContext
     * @param JsysAsiTfaService $tfaService
     * @param JsysAsiLockService $lockService
     * @param JsysAsiNotificationService $notifyService
     * @param ConfigRepository $configRepo
     * @param MemberRepository $memberRepo
     * @param JsysAsiTfaUserRepository $tfaUserRepo
     * @param JsysAsiTfaOtpHistoryRepository $otpHistoryRepo
     * @param JsysAsiLoginHistoryRepository $loginHistoryRepo
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        \Twig_Environment $twig,
        RequestStack $requestStack,
        Context $requestContext,
        JsysAsiTfaService $tfaService,
        JsysAsiLockService $lockService,
        JsysAsiNotificationService $notifyService,
        ConfigRepository $configRepo,
        MemberRepository $memberRepo,
        JsysAsiTfaUserRepository $tfaUserRepo,
        JsysAsiTfaOtpHistoryRepository $otpHistoryRepo,
        JsysAsiLoginHistoryRepository $loginHistoryRepo
    ) {
        $this->entityManager    = $entityManager;
        $this->twig             = $twig;
        $this->requestStack     = $requestStack;
        $this->requestContext   = $requestContext;
        $this->tfaService       = $tfaService;
        $this->lockService      = $lockService;
        $this->notifyService    = $notifyService;
        $this->memberRepo       = $memberRepo;
        $this->tfaUserRepo      = $tfaUserRepo;
        $this->otpHistoryRepo   = $otpHistoryRepo;
        $this->loginHistoryRepo = $loginHistoryRepo;
        $this->Config           = $configRepo->get();
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN                         => 'onInteractiveLogin',
            AuthenticationEvents::AUTHENTICATION_FAILURE              => 'onAuthenticationFailure',
            EccubeEvents::ADMIN_SETTING_SYSTEM_MEMBER_DELETE_COMPLETE => 'onAdminMemberDeleteComplete',
            '@admin/login.twig'                                       => 'onAdminRenderLogin',
        ];
    }

    /**
     * ログイン成功
     *  - 管理ログイン時、2要素認証有効であれば認証コードチェックを実行
     * @param InteractiveLoginEvent $event
     * @throws CustomUserMessageAuthenticationException
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
        if (!$user instanceof Member) {
            return;
        }

        $invalid = false;
        $code    = $event->getRequest()->get('jsys_asi_otp');
        $ip      = $this->requestStack->getMasterRequest()->getClientIp();

        // 2要素認証が有効なら認証コードをチェック
        $tfaStatus = JsysAsiLoginHistoryTfaStatus::DISABLED;
        if ($this->Config->getOptionTfa()) {
            if (!$this->tfaService->isValidCode($user, $code)) {
                $invalid = true;
                $result  = $this->tfaService->getTfaResult();
                if ($result == JsysAsiTfaService::RESULT_TFA_USED) {
                    $tfaStatus = JsysAsiLoginHistoryTfaStatus::USED;
                } else {
                    $tfaStatus = JsysAsiLoginHistoryTfaStatus::INVALID;
                }

                log_info('JsysAsi 管理 ログイン 不正認証コード', [
                    'member_id' => $user->getId(),
                    'code'      => $code,
                ]);
            } else {
                $tfaStatus = JsysAsiLoginHistoryTfaStatus::VALID;
            }
        }

        // IPアドレスロックが有効ならロック済みかチェック
        $lockStatus = JsysAsiLoginHistoryLockStatus::DISABLED;
        if ($this->Config->getOptionIpAddressLock()) {
            if ($this->lockService->isLockedIpAddress($ip)) {
                $invalid    = true;
                $lockStatus = JsysAsiLoginHistoryLockStatus::LOCKED;
                log_info('JsysAsi 管理 ログイン ロック済みIPアドレス', [
                    'member_id'  => $user->getId(),
                    'ip_address' => $ip,
                ]);
            } else {
                $lockStatus = JsysAsiLoginHistoryLockStatus::UNLOCKED;
            }
        }

        // ログイン成功・拒否処理
        if ($invalid) {
            // 2要素認証・IPアドレスロックによりログイン拒否
            // ログイン履歴へ保存
            $this->loginHistoryRepo->saveHistory(
                $user->getLoginId(),
                $user->getLoginDate(),
                JsysAsiLoginHistoryStatus::DENY,
                $tfaStatus,
                $lockStatus
            );

            // ログインに連続で失敗している場合はIPアドレスをロック
            if($this->lockService->shouldLockIpAddress($ip)) {
                $this->lockService->lockIpAddress($ip);
            }

            // ログイン失敗通知が有効ならログイン失敗メールを送信
            if ($this->Config->getOptionLoginFailureMail()) {
                $this->notifyService->sendLoginFailureMail($user->getLoginId());
            }

            $event->getRequest()->getSession()->set('_security.last_username',
                $user->getLoginId()
            );
            throw new CustomUserMessageAuthenticationException(
                'Invalid credentials.'
            );

        } else {
            // ログイン成功
            // ログイン履歴へログイン成功を保存
            $this->loginHistoryRepo->saveHistory(
                $user->getLoginId(),
                $user->getLoginDate(),
                JsysAsiLoginHistoryStatus::SUCCESS,
                $tfaStatus,
                $lockStatus
            );

            // ログイン成功通知が有効ならログイン成功メールを送信
            if ($this->Config->getOptionLoginSuccessMail()) {
                $this->notifyService->sendLoginSuccessMail($user);
            }
        }
    }

    /**
     * ログイン失敗
     *  - 管理ログイン時、2要素認証有効であれば認証コードチェックを実行
     * @param AuthenticationFailureEvent $event
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        if (!$this->requestContext->isAdmin()) {
            return;
        }

        $token   = $event->getAuthenticationToken();
        $request = $this->requestStack->getMasterRequest();
        $ip      = $request->getClientIp();
        $code    = $request->request->get('jsys_asi_otp');
        $user    = $token->getUser();

        // メンバーが存在 かつ 2要素認証が有効なら認証コードをチェック
        /** @var Member $Member */
        $Member    = $this->memberRepo->findOneBy(['login_id' => $user]);
        $tfaStatus = JsysAsiLoginHistoryTfaStatus::DISABLED;
        if ($Member && $this->Config->getOptionTfa()) {
            if (!$this->tfaService->isValidCode($Member, $code)) {
                $result  = $this->tfaService->getTfaResult();
                if ($result == JsysAsiTfaService::RESULT_TFA_USED) {
                    $tfaStatus = JsysAsiLoginHistoryTfaStatus::USED;
                } else {
                    $tfaStatus = JsysAsiLoginHistoryTfaStatus::INVALID;
                }

                log_info('JsysAsi 管理 ログイン失敗 不正認証コード', [
                    'member_id' => $Member->getId(),
                    'code'      => $code,
                ]);
            } else {
                $tfaStatus = JsysAsiLoginHistoryTfaStatus::VALID;
            }
        }

        // IPアドレスロックが有効ならロック済みかチェック
        $lockStatus = JsysAsiLoginHistoryLockStatus::DISABLED;
        if ($this->Config->getOptionIpAddressLock()) {
            if ($this->lockService->isLockedIpAddress($ip)) {
                $lockStatus = JsysAsiLoginHistoryLockStatus::LOCKED;
                log_info('JsysAsi 管理 ログイン失敗 ロック済みIPアドレス', [
                    'login_id'   => $user,
                    'ip_address' => $ip,
                ]);
            } else {
                $lockStatus = JsysAsiLoginHistoryLockStatus::UNLOCKED;
            }
        }

        // ログイン履歴へログイン失敗を保存
        $this->loginHistoryRepo->saveHistory(
            $user,
            new \DateTime(),
            JsysAsiLoginHistoryStatus::FAILURE,
            $tfaStatus,
            $lockStatus
        );

        // ログインに連続で失敗している場合はIPアドレスをロック
        if($this->lockService->shouldLockIpAddress($ip)) {
            $this->lockService->lockIpAddress($ip);
        }

        // ログイン失敗通知が有効ならログイン失敗メールを送信
        if ($this->Config->getOptionLoginFailureMail()) {
            $this->notifyService->sendLoginFailureMail($user);
        }
    }

    /**
     * 管理 > 設定 > システム設定 > メンバー管理 > 削除
     *  - 2要素認証ユーザーとOTP履歴を削除
     * @param EventArgs $event
     */
    public function onAdminMemberDeleteComplete(EventArgs $event)
    {
        // メンバーが存在しない2要素認証ユーザーを全て取得
        $tfaUsers = $this->tfaUserRepo->createQueryBuilder('u')
            ->select()
            ->leftJoin(Member::class, 'm', Join::WITH, 'm.id = u.member_id')
            ->where('m.id IS NULL')
            ->getQuery()
            ->getResult();

        // 削除されたメンバーのOTP履歴と2要素認証ユーザーを削除
        foreach ($tfaUsers as $tfaUser) {
            $this->otpHistoryRepo->deleteByTfaUser($tfaUser);

            $this->entityManager->remove($tfaUser);
            $this->entityManager->flush($tfaUser);
            log_info('JsysAsi 2要素認証ユーザー 削除', [ 'JsysAsiTfaUser' => $tfaUser]);
        }
    }

    /**
     * 管理 > ログイン画面
     *  - 2要素認証有効時にOTPを追加
     * @param TemplateEvent $event
     */
    public function onAdminRenderLogin(TemplateEvent $event)
    {
        // 2要素認証が有効なら認証コードを画面に追加
        if ($this->Config->getOptionTfa()) {
            $this->addOtpTwigToAdminLogin($event);
        }
    }


    /**
     * 管理ログイン画面に認証コードを追加します。
     * @param TemplateEvent $event
     */
    private function addOtpTwigToAdminLogin(TemplateEvent $event)
    {
        /*
         * admin/login.twigにsnippet.twigのincludeがないため、ソースへ直接追加
         */
        $tpl    = '@JsysAsi/admin/login_ext_otp.twig';
        $otp    = $this->twig->getLoader()->getSourceContext($tpl)->getCode();
        $search = '{% if error %}';
        $event->setSource(str_replace($search, $otp . $search, $event->getSource()));
    }

}
