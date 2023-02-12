<?php

namespace Plugin\EccubePaymentLite4\Service;

use Eccube\Service\Payment\PaymentDispatcher;
use Plugin\EccubePaymentLite4\Entity\Config;
use Plugin\EccubePaymentLite4\Entity\IpBlackList;
use Plugin\EccubePaymentLite4\Repository\ConfigRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class IpBlackListService
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var Environment
     */
    private $twig;

    public function __construct(
        ConfigRepository $configRepository,
        Environment $twig
    ) {
        $this->configRepository = $configRepository;
        $this->twig = $twig;
    }

    public function handle()
    {
        // ブラックリストのIPを取得
        $request = Request::createFromGlobals();
        $remoteAddress = $request->server->get('REMOTE_ADDR');

        $dispatcher = new PaymentDispatcher();
        /** @var Config $Config */
        $Config = $this->configRepository->find(1);
        foreach ($Config->getIpBlackList() as $ipBlackList) {
            /** @var ipBlacklist $ipBlackList */
            if ($ipBlackList->getIpAddress() === $remoteAddress) {
                $content = $this->twig->render('error.twig', [
                    'error_title' => trans('front.shopping.error'),
                    'error_message' => trans('front.shopping.order_error'),
                ]);
                $dispatcher->setResponse(Response::create($content));
            }
        }

        return $dispatcher;
    }
}
