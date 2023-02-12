<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Admin\Product;

use Eccube\Entity\Master\SaleType;
use Eccube\Entity\Product;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\Master\SaleTypeRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddRegularCycleFormToProductClassType implements EventSubscriberInterface
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
            '@admin/Product/product.twig' => 'product',
        ];
    }

    public function product(TemplateEvent $event)
    {
        /** @var Product $Product */
        $Product = $event->getParameter('Product');
        if ($Product->getProductClasses()->count() > 1) {
            return;
        }
        /** @var SaleType $SaleType */
        $SaleType = $this->saleTypeRepository->findOneBy([
            'name' => '定期商品',
        ]);
        if (is_null($SaleType)) {
            return;
        }
        $event->setParameter('regularSaleTypeId', $SaleType->getId());
        $event->addSnippet('@EccubePaymentLite4/admin/Product/regular_cycle_form.twig');
    }
}
