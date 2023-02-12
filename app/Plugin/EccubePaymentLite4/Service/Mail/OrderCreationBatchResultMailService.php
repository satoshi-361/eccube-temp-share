<?php

namespace Plugin\EccubePaymentLite4\Service\Mail;

use Eccube\Repository\BaseInfoRepository;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Swift_Mailer;
use Twig\Environment;

class OrderCreationBatchResultMailService
{
    /**
     * @var Swift_Mailer
     */
    private $mailer;
    /**
     * @var Environment
     */
    private $twig;
    /**
     * @var BaseInfoRepository
     */
    private $baseInfoRepository;
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(
        Swift_Mailer $mailer,
        Environment $twig,
        BaseInfoRepository $baseInfoRepository,
        ConfigRepository $configRepository
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->baseInfoRepository = $baseInfoRepository;
        $this->configRepository = $configRepository;
    }

    /**
     * @param $PaymentErrorRegularOrders
     *
     * @return int|void
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function sendMail(
        $PaymentErrorRegularOrders,
        int $numberOfRegularOrders,
        int $numberOfCompletedOrder,
        int $numberOfPaymentError,
        int $numberOfSystemError)
    {
        $body = $this->twig->render('EccubePaymentLite4/Resource/template/default/Mail/order_creation_batch_result_mail.twig', [
            'now' => new \DateTime(),
            'PaymentErrorRegularOrders' => $PaymentErrorRegularOrders,
            'numberOfRegularOrders' => $numberOfRegularOrders,
            'numberOfCompletedOrder' => $numberOfCompletedOrder,
            'numberOfPaymentError' => $numberOfPaymentError,
            'numberOfSystemError' => $numberOfSystemError,
        ]);

        $BaseInfo = $this->baseInfoRepository->find(1);
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        $message = (new \Swift_Message())
            ->setSubject('['.$BaseInfo->getShopName().'] '.'受注作成バッチ実行結果通知メール')
            ->setFrom([$BaseInfo->getEmail01() => $BaseInfo->getShopName()])
            ->setTo($Config->getRegularOrderNotificationEmail())
            ->setReplyTo($BaseInfo->getEmail03())
            ->setReturnPath($BaseInfo->getEmail04());
        $message->setBody($body);

        return $this->mailer->send($message);
    }
}
