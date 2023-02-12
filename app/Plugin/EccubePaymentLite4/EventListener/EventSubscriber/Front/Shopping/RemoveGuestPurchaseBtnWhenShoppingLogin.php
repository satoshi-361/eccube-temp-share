<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Shopping;

use Eccube\Entity\CartItem;
use Eccube\Event\TemplateEvent;
use Eccube\Service\CartService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RemoveGuestPurchaseBtnWhenShoppingLogin implements EventSubscriberInterface
{
    /**
     * @var CartService
     */
    private $cartService;

    public function __construct(
        CartService $cartService
    ) {
        $this->cartService = $cartService;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopping/login.twig' => 'index',
        ];
    }

    public function index(TemplateEvent $templateEvent)
    {
        $Cart = $this->cartService->getCart();
        /* @var CartItem $cartItem */
        $cartItem = $Cart->getItems()->first();
        if ($cartItem->getProductClass()->getSaleType()->getName() !== '定期商品') {
            return;
        }
        $templateEvent
            ->addSnippet('@EccubePaymentLite4/default/Shopping/remove_guest_purchase_btn.twig');
    }
}
