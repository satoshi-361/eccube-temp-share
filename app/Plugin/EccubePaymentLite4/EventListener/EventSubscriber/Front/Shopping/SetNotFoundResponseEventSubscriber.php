<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Front\Shopping;

use Eccube\Entity\Master\SaleType;
use Eccube\Entity\OrderItem;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\Master\SaleTypeRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SetNotFoundResponseEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var SaleTypeRepository
     */
    private $saleTypeRepository;

    public function __construct(
        SaleTypeRepository $saleTypeRepository
    ) {
        $this->saleTypeRepository = $saleTypeRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopping/shipping_multiple.twig' => 'index',
        ];
    }

    public function index(TemplateEvent $templateEvent)
    {
        /** @var OrderItem[] $OrderItems */
        $OrderItems = $templateEvent->getParameter('OrderItems');
        foreach ($OrderItems as $OrderItem) {
            /** @var SaleType $SaleType */
            $SaleType = $OrderItem
                ->getShipping()
                ->getDelivery()
                ->getSaleType();
            if ($SaleType->getName() === '定期商品') {
                throw new NotFoundHttpException();
            }
        }
    }
}
