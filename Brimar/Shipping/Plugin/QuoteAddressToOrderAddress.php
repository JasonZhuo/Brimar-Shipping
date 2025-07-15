<?php
/**
 * Created By: jason Zhuo
 * 7/14/2025
 */

namespace Brimar\Shipping\Plugin;

class QuoteAddressToOrderAddress
{
    public function aroundConvert(
        \Magento\Quote\Model\Quote\Address\ToOrderAddress $subject,
        callable $proceed,
        \Magento\Quote\Model\Quote\Address $quoteAddress,
        $data = []
    ) {
        $orderAddress = $proceed($quoteAddress, $data);
        
        $extensionAttributes = $quoteAddress->getExtensionAttributes();
        if ($extensionAttributes) {
            $orderAddress->setBrimarResidential($extensionAttributes->getBrimarResidential());
            $orderAddress->setBrimarScheduled($extensionAttributes->getBrimarScheduled());
        }
        
        return $orderAddress;
    }
}