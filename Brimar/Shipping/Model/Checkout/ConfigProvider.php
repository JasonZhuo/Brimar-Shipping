<?php
/**
 * Created By: jason Zhuo
 * 7/14/2025
 */

namespace Brimar\Shipping\Model\Checkout;

use Magento\Checkout\Model\ConfigProviderInterface;
use Psr\Log\LoggerInterface;

class ConfigProvider implements ConfigProviderInterface
{
    protected $scopeConfig;
    protected $logger;

    public function __construct(
        LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    public function getConfig()
    {
        $residential = $this->scopeConfig->getValue(
            'carriers/brimar/surcharge_residential',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        
        $scheduled = $this->scopeConfig->getValue(
            'carriers/brimar/surcharge_scheduled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        // debug the config values
        /*$this->logger->info("Brimar Config Values:", [
            'residential' => $residential,
            'scheduled' => $scheduled
        ]);*/

        return [
            'brimarShipping' => [
                'residentialSurcharge' => (float)$residential ?: 2.0,
                'scheduledSurcharge' => (float)$scheduled ?: 3.0
            ]
        ];
    }
}