<?php

namespace Plugin\EccubePaymentLite4\Service\PurchaseFlow\Processor;

use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Request\Context;
use Eccube\Service\PurchaseFlow\ItemHolderValidator;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ShoppingFlow
 */
class MemberCheckForRegularPurchaseValidator extends ItemHolderValidator
{
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var Context
     */
    private $context;

    public function __construct(
        ContainerInterface $container,
        Context $context
    ) {
        $this->container = $container;
        $this->context = $context;
    }

    protected function validate(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        if ($this->context->isAdmin()) {
            return;
        }
        // Command実行時はチェックを行わないため以下のチェックを行う
        if (!$this->context->isFront()) {
            return;
        }
        /** @var Order $Order */
        $Order = $itemHolder;
        if ($Order->getShippings()->first()) {
            // 定期商品ではない場合は処理をスキップ
            if ($Order->getShippings()->first()->getDelivery()->getSaleType()->getName() !== '定期商品') {
                return;
            }
            // ログイン済みの場合は処理をスキップ
            if (!is_null($this->getUser())) {
                return;
            }
            $this->throwInvalidItemException('定期商品は、会員登録が必要です。お手数ですが、会員登録をお願いします。', null, false);
        }
    }

    private function getUser()
    {
        if (!$this->container->has('security.token_storage')) {
            throw new \LogicException('The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".');
        }

        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
            return null;
        }

        if (!\is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return null;
        }

        return $user;
    }
}
