<?php
/**
 * Created By: jason Zhuo
 * 7/14/2025
 */

/**
 * Class ShippingInfo
 * @package Brimar\Shipping\Block\Order
 */

namespace Brimar\Shipping\Block\Order;

class ShippingInfo extends \Magento\Framework\View\Element\Template
{
    public function getBrimarShippingInfo()
    {
        $order = $this->getParentBlock()->getOrder();
        $info = [];
        
        if ($order->getShippingMethod() === 'brimar_brimar') {
            if ($order->getBrimarResidential()) {
                $info[] = __('Residential Delivery');
            }
            if ($order->getBrimarScheduled()) {
                $info[] = __('Scheduled Delivery');
            }
            
            if ($order->getBrimarShippingFee() > 0) {
                $info[] = __('Additional Fees: %1', $order->formatPrice($order->getBrimarShippingFee()));
            }
        }
        
        return $info;
    }
}