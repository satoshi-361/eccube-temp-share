<?php

namespace Plugin\EccubePaymentLite4\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Service\Payment\PaymentDispatcher;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\CreditAccessLog;
use Plugin\EccubePaymentLite4\Entity\CreditBlock;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Repository\CreditAccessLogRepository;
use Plugin\EccubePaymentLite4\Repository\CreditBlockRepository;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class AccessBlockProcessService
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var object|null
     */
    private $Config;
    /**
     * @var CreditAccessLogRepository
     */
    private $creditAccessLogRepository;
    /**
     * @var CreditBlockRepository
     */
    private $creditBlockRepository;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var Environment
     */
    private $twig;

    public function __construct(
        ConfigRepository $configRepository,
        CreditAccessLogRepository $creditAccessLogRepository,
        CreditBlockRepository $creditBlockRepository,
        EntityManagerInterface $entityManager,
        Environment $twig
    ) {
        $this->configRepository = $configRepository;
        $this->creditAccessLogRepository = $creditAccessLogRepository;
        $this->creditBlockRepository = $creditBlockRepository;
        $this->entityManager = $entityManager;
        $this->twig = $twig;
        /* @var Config Config */
        $this->Config = $this->configRepository->find(1);
    }

    /**
     * 不正アクセスブロック処理
     *
     * @return PaymentDispatcher $dispatcher
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function check(): PaymentDispatcher
    {
        $dispatcher = new PaymentDispatcher();

        $block_mode = $this->Config->getBlockMode();

        if ($block_mode) {
            $block_flg = false;

            // アクセス頻度時間を過ぎたIPアドレスを削除
            $this->creditAccessLogRepository->deleteAllIpAddressForPassedAccessFrequencyTime($this->Config->getAccessFrequencyTime());
            // ブロック時間を過ぎたIPアドレスを削除
            $this->creditBlockRepository->deleteAllIpAddressForPassedBlockTime($this->Config->getBlockTime());

            $arrWhiteList = explode(',', $this->Config->getWhiteList());
            $is_registed_whiteList = in_array($_SERVER['REMOTE_ADDR'], $arrWhiteList);
            if (!$is_registed_whiteList) {
                $this->registCreditAccessLog();
            }

            if ($this->isAlreadyBlockedCreditAccess()) {
                $block_flg = true;
            } elseif (!$is_registed_whiteList) {
                $block_flg = $this->judgeAccessBlocking();
            }

            if ($block_flg && !$is_registed_whiteList) {
                $err_detail = 'message';
                $content = $this->twig->render('error.twig', [
                    'error_title' => trans('front.shopping.error'),
                    'error_message' => trans('front.shopping.order_error'),
                ]);
                $dispatcher->setResponse(Response::create($content));
            }
        }

        return $dispatcher;
    }

    /**
     * 不正アクセスをブロックする
     */
    private function judgeAccessBlocking()
    {
        $creditAccessLog = $this->creditAccessLogRepository->findBy(['ip_address' => $_SERVER['REMOTE_ADDR']]);

        $CreditBlock = new CreditBlock();
        if (count($creditAccessLog) >= $this->Config->getAccessFrequency()) {
            // 不正アクセスと判断
            $date = new \DateTime();
            logs('gmo_epsilon')->info("access block IPADDRESS:$_SERVER[REMOTE_ADDR] DATE:{$date->format('Y-m-d H:i:s')}");

            $CreditBlock->setIpAddress($_SERVER['REMOTE_ADDR']);
            $CreditBlock->setBlockDate(new \DateTime());

            $this->entityManager->persist($CreditBlock);
            $this->entityManager->flush();

            return true;
        }

        return false;
    }

    /**
     * クレジットアクセスログを記録する
     */
    private function registCreditAccessLog()
    {
        $date = new \DateTime();
        $CreditAccessLog = new CreditAccessLog();
        logs('gmo_epsilon')->info("regist access log IPADDRESS:$_SERVER[REMOTE_ADDR] DATE:{$date->format('Y-m-d H:i:s')}");
        $CreditAccessLog->setIpAddress($_SERVER['REMOTE_ADDR']);
        $CreditAccessLog->setAccessDate($date);

        $this->entityManager->persist($CreditAccessLog);
        $this->entityManager->flush();
    }

    /**
     * 既に不正アクセスとしてブロックされていないか確認する
     */
    private function isAlreadyBlockedCreditAccess()
    {
        $CreditBlock = $this->creditBlockRepository->findBy(['ip_address' => $_SERVER['REMOTE_ADDR']]);

        return $CreditBlock ? true : false;
    }
}
