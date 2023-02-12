<?php

namespace Plugin\EccubePaymentLite4\Service\Mail;

use Eccube\Entity\MailTemplate;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\MailTemplateRepository;
use Eccube\Service\MailService;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Plugin\EccubePaymentLite4\Repository\RegularShippingRepository;
use Swift_Mailer;
use Twig\Environment;

class RegularSpecifiedCountNotificationMailService
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
     * @var MailService
     */
    private $mailService;
    /**
     * @var MailTemplateRepository
     */
    private $mailTemplateRepository;
    /**
     * @var RegularShippingRepository
     */
    private $regularShippingRepository;
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(
        Swift_Mailer $mailer,
        Environment $twig,
        BaseInfoRepository $baseInfoRepository,
        MailTemplateRepository $mailTemplateRepository,
        MailService $mailService,
        RegularShippingRepository $regularShippingRepository,
        ConfigRepository $configRepository
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->baseInfoRepository = $baseInfoRepository;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->mailService = $mailService;
        $this->regularShippingRepository = $regularShippingRepository;
        $this->configRepository = $configRepository;
    }

    public function sendMail(RegularOrder $RegularOrder)
    {
        $Customer = $RegularOrder->getCustomer();
        $BaseInfo = $this->baseInfoRepository->find(1);
        /** @var MailTemplate $MailTemplate */
        $MailTemplate = $this->mailTemplateRepository->findOneBy([
            'file_name' => 'EccubePaymentLite4/Resource/template/default/Mail/specified_count_notice_mail.twig',
        ]);

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'BaseInfo' => $BaseInfo,
            'RegularOrder' => $RegularOrder,
        ]);

        $message = (new \Swift_Message())
            ->setSubject('['.$BaseInfo->getShopName().'] '.$MailTemplate->getMailSubject())
            ->setFrom([$BaseInfo->getEmail01() => $BaseInfo->getShopName()])
            ->setTo([$Customer->getEmail()])
            ->setReplyTo($BaseInfo->getEmail03())
            ->setReturnPath($BaseInfo->getEmail04());
        $htmlFileName = $this->mailService->getHtmlTemplate($MailTemplate->getFileName());
        if (!is_null($htmlFileName)) {
            $htmlBody = $this->twig->render($htmlFileName, [
                'BaseInfo' => $BaseInfo,
                'RegularOrder' => $RegularOrder,
            ]);

            $message
                ->setContentType('text/plain; charset=UTF-8')
                ->setBody($body, 'text/plain')
                ->addPart($htmlBody, 'text/html');
        } else {
            $message->setBody($body);
        }

        return $this->mailer->send($message);
    }
}
