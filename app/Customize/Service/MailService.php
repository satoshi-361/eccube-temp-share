<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\Service;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Customer;
use Eccube\Entity\MailHistory;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\MailHistoryRepository;
use Eccube\Repository\MailTemplateRepository;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Eccube\Service\MailService as BaseService;
use Eccube\Repository\CustomerRepository;
use Eccube\Entity\Product;

class MailService extends BaseService
{
    private $customerRepository;

    /**
     * MailService constructor.
     *
     * @param \Swift_Mailer $mailer
     * @param MailTemplateRepository $mailTemplateRepository
     * @param MailHistoryRepository $mailHistoryRepository
     * @param BaseInfoRepository $baseInfoRepository
     * @param EventDispatcherInterface $eventDispatcher
     * @param \Twig_Environment $twig
     * @param EccubeConfig $eccubeConfig
     * @param CustomerRepository $customerRepository
     */
    public function __construct(
        \Swift_Mailer $mailer,
        MailTemplateRepository $mailTemplateRepository,
        MailHistoryRepository $mailHistoryRepository,
        BaseInfoRepository $baseInfoRepository,
        EventDispatcherInterface $eventDispatcher,
        \Twig_Environment $twig,
        CustomerRepository $customerRepository,
        EccubeConfig $eccubeConfig
    ) {
        parent::__construct( $mailer, $mailTemplateRepository, $mailHistoryRepository, $baseInfoRepository, $eventDispatcher, $twig, $eccubeConfig );
        $this->customerRepository = $customerRepository;
    }

    /**
     * Send order mail.
     *
     * @param \Eccube\Entity\Order $Order 受注情報
     *
     * @return \Swift_Message
     */
    public function sendOrderMail(Order $Order)
    {
        log_info('受注メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_order_mail_template_id']);

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'Order' => $Order,
        ]);

        $message = (new \Swift_Message())
            ->setSubject('['.$this->BaseInfo->getShopName().'] '.$MailTemplate->getMailSubject())
            ->setFrom([$this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()])
            ->setTo([$Order->getEmail()])
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04());

        // HTMLテンプレートが存在する場合
        $htmlFileName = $this->getHtmlTemplate($MailTemplate->getFileName());
        if (!is_null($htmlFileName)) {
            $htmlBody = $this->twig->render($htmlFileName, [
                'Order' => $Order,
            ]);

            $message
                ->setContentType('text/plain; charset=UTF-8')
                ->setBody($body, 'text/plain')
                ->addPart($htmlBody, 'text/html');
        } else {
            $message->setBody($body);
        }

        $event = new EventArgs(
            [
                'message' => $message,
                'Order' => $Order,
                'MailTemplate' => $MailTemplate,
                'BaseInfo' => $this->BaseInfo,
            ],
            null
        );
        $this->eventDispatcher->dispatch(EccubeEvents::MAIL_ORDER, $event);

        $count = $this->mailer->send($message);

        $MailHistory = new MailHistory();
        $MailHistory->setMailSubject($message->getSubject())
            ->setMailBody($message->getBody())
            ->setOrder($Order)
            ->setSendDate(new \DateTime());

        // HTML用メールの設定
        $multipart = $message->getChildren();
        if (count($multipart) > 0) {
            $MailHistory->setMailHtmlBody($multipart[0]->getBody());
        }

        $this->mailHistoryRepository->save($MailHistory);

        log_info('受注メール送信完了', ['count' => $count]);

        foreach ( $Order->getMergedProductOrderItems() as $OrderItem ) {
            if ( $OrderItem->getAffiliater() ) {
                // 提携された場合、アフィリエイト成功時に届くメール配信する
                $this->sendAffiliateMail($OrderItem);
            }
            
            // 記事が売れた時に販売者に届くメール
            $this->sendSaleMail($OrderItem);
        }

        return $message;
    }
    
    /**
     * Send affiliate mail.
     *
     * @param \Eccube\Entity\Order $Order 受注情報
     *
     * @return \Swift_Message
     */
    public function sendAffiliateMail(OrderItem $OrderItem)
    {
        log_info('提携メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_affiliate_mail_template_id']);
        $Order = $OrderItem->getOrder();
        $Affiliater = $this->customerRepository->find($OrderItem->getAffiliater());

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'Order' => $Order,
            'OrderItem' => $OrderItem,
            'Affiliater' => $Affiliater,
        ]);

        $message = (new \Swift_Message())
            ->setSubject('['.$this->BaseInfo->getShopName().'] '.$MailTemplate->getMailSubject())
            ->setFrom([$this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()])
            ->setTo([$Affiliater->getEmail()])
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04());

        // HTMLテンプレートが存在する場合
        $htmlFileName = $this->getHtmlTemplate($MailTemplate->getFileName());
        if (!is_null($htmlFileName)) {
            $htmlBody = $this->twig->render($htmlFileName, [
                'Order' => $Order,
                'OrderItem' => $OrderItem,
                'Affiliater' => $Affiliater,
            ]);

            $message
                ->setContentType('text/plain; charset=UTF-8')
                ->setBody($body, 'text/plain')
                ->addPart($htmlBody, 'text/html');
        } else {
            $message->setBody($body);
        }

        $count = $this->mailer->send($message);

        $MailHistory = new MailHistory();
        $MailHistory->setMailSubject($message->getSubject())
            ->setMailBody($message->getBody())
            ->setOrder($Order)
            ->setSendDate(new \DateTime());

        // HTML用メールの設定
        $multipart = $message->getChildren();
        if (count($multipart) > 0) {
            $MailHistory->setMailHtmlBody($multipart[0]->getBody());
        }

        $this->mailHistoryRepository->save($MailHistory);

        log_info('提携メール送信完了', ['count' => $count]);

        return $message;
    }    
    
    /**
     * Send sale mail.
     *
     * @param \Eccube\Entity\Order $Order 受注情報
     *
     * @return \Swift_Message
     */
    public function sendSaleMail(OrderItem $OrderItem)
    {
        log_info('販売メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_sale_mail_template_id']);
        $Order = $OrderItem->getOrder();
        $Saler = $OrderItem->getProduct()->getCustomer();

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'Order' => $Order,
            'OrderItem' => $OrderItem,
            'Saler' => $Saler,
        ]);

        $message = (new \Swift_Message())
            ->setSubject('['.$this->BaseInfo->getShopName().'] '.$MailTemplate->getMailSubject())
            ->setFrom([$this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()])
            ->setTo([$Saler->getEmail()])
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04());

        // HTMLテンプレートが存在する場合
        $htmlFileName = $this->getHtmlTemplate($MailTemplate->getFileName());
        if (!is_null($htmlFileName)) {
            $htmlBody = $this->twig->render($htmlFileName, [
                'Order' => $Order,
                'OrderItem' => $OrderItem,
                'Saler' => $Saler,
            ]);

            $message
                ->setContentType('text/plain; charset=UTF-8')
                ->setBody($body, 'text/plain')
                ->addPart($htmlBody, 'text/html');
        } else {
            $message->setBody($body);
        }

        $count = $this->mailer->send($message);

        $MailHistory = new MailHistory();
        $MailHistory->setMailSubject($message->getSubject())
            ->setMailBody($message->getBody())
            ->setOrder($Order)
            ->setSendDate(new \DateTime());

        // HTML用メールの設定
        $multipart = $message->getChildren();
        if (count($multipart) > 0) {
            $MailHistory->setMailHtmlBody($multipart[0]->getBody());
        }

        $this->mailHistoryRepository->save($MailHistory);

        log_info('販売メール送信完了', ['count' => $count]);

        return $message;
    }
    
    /**
     * Send article approve mail.
     *
     * @param \Eccube\Entity\Product $Blog 記事
     *
     * @return \Swift_Message
     */
    public function sendArticleApproveMail(Product $Blog)
    {
        log_info('記事承認メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_article_approve_mail_template_id']);

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'Blog' => $Blog,
        ]);

        $message = (new \Swift_Message())
            ->setSubject('['.$this->BaseInfo->getShopName().'] '.$MailTemplate->getMailSubject())
            ->setFrom([$this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()])
            ->setTo([$Blog->getCustomer()->getEmail()])
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04());

        // HTMLテンプレートが存在する場合
        $htmlFileName = $this->getHtmlTemplate($MailTemplate->getFileName());
        if (!is_null($htmlFileName)) {
            $htmlBody = $this->twig->render($htmlFileName, [
                'Blog' => $Blog,
            ]);

            $message
                ->setContentType('text/plain; charset=UTF-8')
                ->setBody($body, 'text/plain')
                ->addPart($htmlBody, 'text/html');
        } else {
            $message->setBody($body);
        }

        $count = $this->mailer->send($message);

        $MailHistory = new MailHistory();
        $MailHistory->setMailSubject($message->getSubject())
            ->setMailBody($message->getBody())
            ->setSendDate(new \DateTime());

        // HTML用メールの設定
        $multipart = $message->getChildren();
        if (count($multipart) > 0) {
            $MailHistory->setMailHtmlBody($multipart[0]->getBody());
        }

        $this->mailHistoryRepository->save($MailHistory);

        log_info('記事承認メール送信完了', ['count' => $count]);

        return $message;
    }
    
    /**
     * Send article no approve mail.
     *
     * @param \Eccube\Entity\Product $Blog 記事
     *
     * @return \Swift_Message
     */
    public function sendArticleNoApproveMail(Product $Blog)
    {
        log_info('記事否認メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_article_no_approve_mail_template_id']);

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'Blog' => $Blog,
        ]);

        $message = (new \Swift_Message())
            ->setSubject('['.$this->BaseInfo->getShopName().'] '.$MailTemplate->getMailSubject())
            ->setFrom([$this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()])
            ->setTo([$Blog->getCustomer()->getEmail()])
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04());

        // HTMLテンプレートが存在する場合
        $htmlFileName = $this->getHtmlTemplate($MailTemplate->getFileName());
        if (!is_null($htmlFileName)) {
            $htmlBody = $this->twig->render($htmlFileName, [
                'Blog' => $Blog,
            ]);

            $message
                ->setContentType('text/plain; charset=UTF-8')
                ->setBody($body, 'text/plain')
                ->addPart($htmlBody, 'text/html');
        } else {
            $message->setBody($body);
        }

        $count = $this->mailer->send($message);

        $MailHistory = new MailHistory();
        $MailHistory->setMailSubject($message->getSubject())
            ->setMailBody($message->getBody())
            ->setSendDate(new \DateTime());

        // HTML用メールの設定
        $multipart = $message->getChildren();
        if (count($multipart) > 0) {
            $MailHistory->setMailHtmlBody($multipart[0]->getBody());
        }

        $this->mailHistoryRepository->save($MailHistory);

        log_info('記事否認メール送信完了', ['count' => $count]);

        return $message;
    }
    
    /**
     * Send transfer success mail.
     *
     * @param \Eccube\Entity\Customer $Customer 会員
     * @param array $data
     *
     * @return \Swift_Message
     */
    public function sendTransferSuccessMail(Customer $Customer, $data = [])
    {
        log_info('振込完了メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_transfer_success_mail_template_id']);
        $month = $data['month'];
        $transferAmount = $data['transfer_money'];

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'Customer' => $Customer,
            'month' => $month,
            'transferAmount' => $transferAmount,
        ]);

        $message = (new \Swift_Message())
            ->setSubject('['.$this->BaseInfo->getShopName().'] '.$MailTemplate->getMailSubject())
            ->setFrom([$this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()])
            ->setTo([$Customer->getEmail()])
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04());

        // HTMLテンプレートが存在する場合
        $htmlFileName = $this->getHtmlTemplate($MailTemplate->getFileName());
        if (!is_null($htmlFileName)) {
            $htmlBody = $this->twig->render($htmlFileName, [
                'Customer' => $Customer,
                'month' => $month,
                'transferAmount' => $transferAmount,
            ]);

            $message
                ->setContentType('text/plain; charset=UTF-8')
                ->setBody($body, 'text/plain')
                ->addPart($htmlBody, 'text/html');
        } else {
            $message->setBody($body);
        }

        $count = $this->mailer->send($message);

        $MailHistory = new MailHistory();
        $MailHistory->setMailSubject($message->getSubject())
            ->setMailBody($message->getBody())
            ->setSendDate(new \DateTime());

        // HTML用メールの設定
        $multipart = $message->getChildren();
        if (count($multipart) > 0) {
            $MailHistory->setMailHtmlBody($multipart[0]->getBody());
        }

        $this->mailHistoryRepository->save($MailHistory);

        log_info('振込完了メール送信完了', ['count' => $count]);

        return $message;
    }
    
    /**
     * Send transfer failed mail.
     *
     * @param \Eccube\Entity\Customer $Customer 会員
     * @param array $data
     *
     * @return \Swift_Message
     */
    public function sendTransferFailedMail(Customer $Customer, $data = [])
    {
        log_info('振込失敗メール送信開始');

        $MailTemplate = $this->mailTemplateRepository->find($this->eccubeConfig['eccube_transfer_failed_mail_template_id']);
        $month = $data['month'];
        $transferAmount = $data['transfer_money'];

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'Customer' => $Customer,
            'month' => $month,
            'transferAmount' => $transferAmount,
        ]);

        $message = (new \Swift_Message())
            ->setSubject('['.$this->BaseInfo->getShopName().'] '.$MailTemplate->getMailSubject())
            ->setFrom([$this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()])
            ->setTo([$Customer->getEmail()])
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04());

        // HTMLテンプレートが存在する場合
        $htmlFileName = $this->getHtmlTemplate($MailTemplate->getFileName());
        if (!is_null($htmlFileName)) {
            $htmlBody = $this->twig->render($htmlFileName, [
                'Customer' => $Customer,
                'month' => $month,
                'transferAmount' => $transferAmount,
            ]);

            $message
                ->setContentType('text/plain; charset=UTF-8')
                ->setBody($body, 'text/plain')
                ->addPart($htmlBody, 'text/html');
        } else {
            $message->setBody($body);
        }

        $count = $this->mailer->send($message);

        $MailHistory = new MailHistory();
        $MailHistory->setMailSubject($message->getSubject())
            ->setMailBody($message->getBody())
            ->setSendDate(new \DateTime());

        // HTML用メールの設定
        $multipart = $message->getChildren();
        if (count($multipart) > 0) {
            $MailHistory->setMailHtmlBody($multipart[0]->getBody());
        }

        $this->mailHistoryRepository->save($MailHistory);

        log_info('振込失敗メール送信完了', ['count' => $count]);

        return $message;
    }
}
