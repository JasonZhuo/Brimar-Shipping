<?php
/**
 * Created By: jason Zhuo
 * 7/14/2025
 */

namespace Brimar\Shipping\Plugin;

class OrderExtensionAttributes
{
    public function afterGetExtensionAttributes(
        \Magento\Sales\Model\Order $subject,
        $result
    ) {
        if ($result === null) {
            return $result;
        }
        
        $result->setBrimarResidential($subject->getBrimarResidential());
        $result->setBrimarScheduled($subject->getBrimarScheduled());
        $result->setBrimarShippingFee($subject->getBrimarShippingFee());
        
        return $result;
    }
}