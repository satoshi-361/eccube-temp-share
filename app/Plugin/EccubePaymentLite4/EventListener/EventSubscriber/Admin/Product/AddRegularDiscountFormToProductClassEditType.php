<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Admin\Product;

use Eccube\Entity\Master\SaleType;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\Master\SaleTypeRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddRegularDiscountFormToProductClassEditType implements EventSubscriberInterface
{
    /**
     * @var SaleTypeRepository
     */
    private $saleTypeRepository;

    public function __construct(SaleTypeRepository $saleTypeRepository)
    {
        $this->saleTypeRepository = $saleTypeRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            '@admin/Product/product_class.twig' => 'index',
        ];
    }

    public function index(TemplateEvent $event): void
    {
        /** @var SaleType $SaleType */
        $SaleType = $this->saleTypeRepository->findOneBy(['name' => '定期商品']);
        if (is_null($SaleType)) {
            return;
        }

        $event->setParameter('regularSaleTypeId', $SaleType->getId());
        $event->addSnippet('@EccubePaymentLite4/admin/Product/ProductClass/regular_discount.twig');
    }
}
