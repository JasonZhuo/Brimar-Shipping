<?php
/**
 * Created By: jason Zhuo
 * 7/14/2025
 */

namespace Brimar\Shipping\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class SaveOrderAttributes implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();
        
        $order->setBrimarResidential($quote->getBrimarResidential());
        $order->setBrimarScheduled($quote->getBrimarScheduled());
        $order->setBrimarShippingFee($quote->getBrimarShippingFee());
    }
}