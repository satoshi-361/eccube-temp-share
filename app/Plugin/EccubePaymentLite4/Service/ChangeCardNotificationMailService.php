<?php

namespace Plugin\EccubePaymentLite4\Service;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Customer;
use Eccube\Entity\MailTemplate;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\MailTemplateRepository;
use Eccube\Service\MailService;
use Twig\Environment;

class ChangeCardNotificationMailService
{
    /**
     * @var \Swift_Mailer
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
     * @var EccubeConfig
     */
    private $eccubeConfig;

    public function __construct(
        \Swift_Mailer $mailer,
        Environment $twig,
        BaseInfoRepository $baseInfoRepository,
        MailTemplateRepository $mailTemplateRepository,
        MailService $mailService,
        EccubeConfig $eccubeConfig
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->baseInfoRepository = $baseInfoRepository;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->mailService = $mailService;
        $this->eccubeConfig = $eccubeConfig;
    }

    public function sendMail(Customer $Customer)
    {
        $BaseInfo = $this->baseInfoRepository->find(1);
        $expireDateTime = $Customer
            ->getGmoEpsilonCreditCardExpirationDate();
        /** @var MailTemplate $MailTemplate */
        $MailTemplate = $this->mailTemplateRepository->findOneBy([
            'file_name' => 'EccubePaymentLite4/Resource/template/default/Mail/expiration_notice_mail.twig',
        ]);
        $body = $this->twig->render($MailTemplate->getFileName(), [
            'BaseInfo' => $BaseInfo,
            'Customer' => $Customer,
            'expireDateTime' => $expireDateTime,
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
                'Customer' => $Customer,
                'expireDateTime' => $expireDateTime,
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
