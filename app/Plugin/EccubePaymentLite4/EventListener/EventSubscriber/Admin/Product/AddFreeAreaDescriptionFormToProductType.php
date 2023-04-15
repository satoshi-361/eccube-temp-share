<?php

namespace Plugin\EccubePaymentLite4\EventListener\EventSubscriber\Admin\Product;

use Eccube\Entity\Master\SaleType;
use Eccube\Entity\Product;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\Master\SaleTypeRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddFreeAreaDescriptionFormToProductType implements EventSubscriberInterface
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
        $sale_type_flg = false;
        if ($Product->getProductClasses()->count() > 1) {
            foreach ($Product->getProductClasses() as $productClass){
                if($productClass->getSaleType()->getName() === '定期商品'){
                    $sale_type_flg = true;
                }
            }
        }

        /** @var SaleType $SaleType */
        $SaleType = $this->saleTypeRepository->findOneBy([
            'name' => '定期商品',
        ]);

        if (is_null($SaleType)) {
            return;
        }

        $event->setParameter('regularSaleTypeId', $SaleType->getId());
        $event->setParameter('sale_type_flg', $sale_type_flg);
        $event->addSnippet('@EccubePaymentLite4/admin/Product/add_free_area_description_form.twig');
    }
}
