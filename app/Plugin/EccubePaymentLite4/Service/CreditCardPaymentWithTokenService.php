<?php

namespace Plugin\EccubePaymentLite4\Service;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\EccubePaymentLite4\Service\GmoEpsilonRequest\RequestDirectCardPaymentService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class CreditCardPaymentWithTokenService
{
    /**
     * @var RequestDirectCardPaymentService
     */
    private $requestDirectCardPaymentService;
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;
    /**
     * @var Environment
     */
    private $twig;
    /**
     * @var GmoEpsilonRequestService
     */
    private $gmoEpsilonRequestService;
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        RequestDirectCardPaymentService $requestDirectCardPaymentService,
        EccubeConfig $eccubeConfig,
        Environment $twig,
        GmoEpsilonRequestService $gmoEpsilonRequestService,
        ContainerInterface $container
    ) {
        $this->requestDirectCardPaymentService = $requestDirectCardPaymentService;
        $this->eccubeConfig = $eccubeConfig;
        $this->twig = $twig;
        $this->gmoEpsilonRequestService = $gmoEpsilonRequestService;
        $this->container = $container;
    }

    public function handle(string $token, string $stCode, $dispatcher, Order $Order)
    {
        $results = $this
            ->requestDirectCardPaymentService
            ->handle(
                $Order,
                1,
                $stCode,
                'shopping_checkout',
                $token
            );
        if ($results['status'] === 'NG') {
            $message = $results['message'];
            $content = $this->twig->render('error.twig', [
                'error_title' => trans('gmo_epsilon.front.shopping.error'),
                'error_message' => $message,
            ]);
            $dispatcher->setResponse(Response::create($content));

            return $dispatcher;
        }

        $Order->setTransCode($results['trans_code']);
        $Order->setGmoEpsilonOrderNo($results['order_number']);

        // 3DS処理（カード会社に接続必要）
        if ($results['result'] === $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['3ds']) {
            // 3Dセキュア認証送信パラメータ1　加盟店様⇒カード会社
            $content = $this->twig->render('@EccubePaymentLite4/default/Shopping/transition_3ds_screen.twig', [
                'AcsUrl' => $results['acsurl'],
                'PaReq' => $results['pareq'],
                'TermUrl' => $this->container->get('router')->generate('eccube_payment_lite4_reception_3ds', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'MD' => $Order->getPreOrderId(),
            ]);
            $dispatcher->setResponse(Response::create($content));

            return $dispatcher;
        }

        // 3DS 2.0 処理（カード会社に接続必要）
        if ($results['result'] === $this->eccubeConfig['gmo_epsilon']['receive_parameters']['result']['3ds2']) {
            // 3D 2.0 セキュア認証送信パラメータ1　加盟店様⇒カード会社
            $content = $this->twig->render('@EccubePaymentLite4/default/Shopping/transition_3ds2_screen.twig', [
                'ACSUrl' => $results['tds2_url'],
                'TermUrl' => $this->container->get('router')->generate('eccube_payment_lite4_reception_3ds2', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'MD' => $Order->getPreOrderId(),
                'PaReq' => $results['pareq'],
            ]);
            // start write log for sent parameters to カード会社に接続必要
            logs('gmo_epsilon')->info('Parameter sent 3DS 2.0 処理（カード会社に接続必要） (ACSUrl):  = '.$results['tds2_url']);
            logs('gmo_epsilon')->info('Parameter sent 3DS 2.0 処理（カード会社に接続必要） (TermUrl):  = '.$this->container->get('router')->generate('eccube_payment_lite4_reception_3ds2', [], UrlGeneratorInterface::ABSOLUTE_URL));
            logs('gmo_epsilon')->info('Parameter sent 3DS 2.0 処理（カード会社に接続必要） (MD):  = '.$Order->getPreOrderId());
            logs('gmo_epsilon')->info('Parameter sent 3DS 2.0 処理（カード会社に接続必要） (PaReq):  = '.$results['pareq']);
            // end write log for sent parameters to カード会社に接続必要
            $dispatcher->setResponse(Response::create($content));

            return $dispatcher;
        }

        return false;
    }
}
