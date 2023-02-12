<?php

/**
 * This service serve for updating a regular order
 * there are some obsorts of cost account that has not developed because of they no change in quantity of order items modify features.
 * if need change payment and delivery method, we need to write some code for two corresponsed function
 */

namespace Plugin\EccubePaymentLite4\Service;

use Eccube\Repository\TaxRuleRepository;
use Eccube\Entity\Master\TaxType;
use Plugin\EccubePaymentLite4\Entity\RegularOrder;
use Plugin\EccubePaymentLite4\Entity\RegularOrderItem;

class ModifyRegularOrderService
{
    /**
     * @var TaxRuleRepository
     */
    private $taxRuleRepository;

    public function __construct(
        TaxRuleRepository $taxRuleRepository
    ) {
        $this->taxRuleRepository = $taxRuleRepository;
    }

    /**
     * Recalculate Total Payment Of Regular Order
     * 
     * @param RegularOrder $RegularOrder
     */
    public function reCalculateAll(RegularOrder $RegularOrder)
    {
        $this->reCalculateRegularOrderItemAll($RegularOrder);
        $this->reCalculateRegularOrderAll($RegularOrder);
    }

    /**
     * Recalculate Regular Order Items
     * 
     * @param RegularOrder $RegularOrder
     */
    public function reCalculateRegularOrderItemAll(RegularOrder $RegularOrder)
    {
        $this->reCalculateTaxationRegularOrderItem($RegularOrder);
    }

    /**
     * Recalculate Regular Order
     * 
     * @param RegularOrder $RegularOrder
     */
    public function reCalculateRegularOrderAll(RegularOrder $RegularOrder)
    {
        $this->reCalculateTaxationRegularOrder($RegularOrder);
        $this->reCalculateSubtotalRegularOrder($RegularOrder);
        $this->reCalculateTotalAmountRegularOrder($RegularOrder);
        $this->reCalculateTotalpaymentRegularOrder($RegularOrder);
    }

    /**
     * Recalculate Taxation Of Regular Order Item
     * 
     * @param RegularOrder $RegularOrder
     */
    public function reCalculateTaxationRegularOrderItem(RegularOrder $RegularOrder)
    {
        /** @var RegularOrderItem[] $RegularOrderItems */
        $RegularOrderItems = $RegularOrder->getRegularOrderItems();
        foreach ($RegularOrderItems as $RegularOrderItem) {
            if (!$RegularOrderItem->isProduct()) {
                continue;
            }
            $ProductClass = $RegularOrderItem->getProductClass();
            try {
                $taxRule = $this->taxRuleRepository->getByRule($ProductClass->getProduct(), $ProductClass);
            } catch (NoResultException $e) {
            }
            $taxRate = $taxRule->getTaxRate();
            $tax = ($taxRate / 100) * ($RegularOrderItem->getPrice());
            $RegularOrderItem
                ->setTax($tax)
                ->setTaxRate($taxRate);
        }
    }

    /**
     * Recalculate Taxation Of Regular Order
     * 
     * @param RegularOrder $RegularOrder
     */
    public function reCalculateTaxationRegularOrder(RegularOrder $RegularOrder)
    {
        $RegularOrderItems = $RegularOrder->getItems();
        $totalTax = 0;
        foreach ($RegularOrderItems as $RegularOrderItem) {
            if ($RegularOrderItem->getTaxType()->getId() == TaxType::TAXATION) {
                $taxPrice = $RegularOrderItem->getTax() * $RegularOrderItem->getQuantity();
                $totalTax += $taxPrice;
            }
        }
        $RegularOrder->setTax($totalTax);
    }

    /**
     * Recalculate Subtotal Of Regular Order
     * 
     * @param RegularOrder $RegularOrder
     */
    public function reCalculateSubtotalRegularOrder(RegularOrder $RegularOrder)
    {
        $RegularOrderItems = $RegularOrder->getItems();
        $subTotal = 0;
        foreach ($RegularOrderItems as $RegularOrderItem) {
            if ($RegularOrderItem->isProduct()) {
                $totalPrice = $RegularOrderItem->getPrice() * $RegularOrderItem->getQuantity();
                if ($RegularOrderItem->getTaxType()->getId() == TaxType::TAXATION) {
                    $totalPrice += $RegularOrderItem->getTax() * $RegularOrderItem->getQuantity();
                }
                $subTotal += $totalPrice;
            }
        }
        $RegularOrder->setSubtotal($subTotal);
    }

    /**
     * Recalculate Total Amount Of Regular Order
     * 
     * @param RegularOrder $RegularOrder
     */
    public function reCalculateTotalAmountRegularOrder(RegularOrder $RegularOrder)
    {
        $totalAmount = $RegularOrder->getSubtotal() +
            $RegularOrder->getDeliveryFeeTotal() +
            $RegularOrder->getCharge() -
            $RegularOrder->getDiscount();
        $RegularOrder->setTotal($totalAmount);
    }

    /**
     * Recalculate Total Payment Of Regular Order
     * 
     * @param RegularOrder $RegularOrder
     */
    public function reCalculateTotalpaymentRegularOrder(RegularOrder $RegularOrder)
    {
        $totalAmountPayment = $RegularOrder->getSubtotal() +
            $RegularOrder->getDeliveryFeeTotal() +
            $RegularOrder->getCharge() -
            $RegularOrder->getDiscount();
        $RegularOrder->setPaymentTotal($totalAmountPayment);
    }
}
