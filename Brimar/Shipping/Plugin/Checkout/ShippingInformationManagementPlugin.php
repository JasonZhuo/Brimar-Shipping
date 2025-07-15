<?php
/**
 * Created By: jason Zhuo
 * 7/14/2025
 */

namespace Brimar\Shipping\Plugin\Checkout;

class ShippingInformationManagementPlugin
{
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {
        $shippingAddress = $addressInformation->getShippingAddress();
        $extAttributes = $shippingAddress->getExtensionAttributes();
        
        if ($extAttributes) {
            // 保存到quote_address表
            $shippingAddress->setData(
                'brimar_residential', 
                (int)$extAttributes->getBrimarResidential()
            );
            $shippingAddress->setData(
                'brimar_scheduled', 
                (int)$extAttributes->getBrimarScheduled()
            );
        }

        return [$cartId, $addressInformation];
    }
}