<?php
/**
 * Created By: jason Zhuo
 * 7/14/2025
 */

namespace Brimar\Shipping\Controller\Address;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Save extends Action
{
    // config path
    const CONFIG_PATH_BASE_PRICE = 'carriers/brimar/price';
    const CONFIG_PATH_RESIDENTIAL_SURCHARGE = 'carriers/brimar/surcharge_residential';
    const CONFIG_PATH_SCHEDULED_SURCHARGE = 'carriers/brimar/surcharge_scheduled';

    protected $resultJsonFactory;
    protected $resourceConnection;
    protected $logger;
    protected $maskedQuoteIdConverter;
    protected $scopeConfig;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger, 
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdConverter,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        $this->maskedQuoteIdConverter = $maskedQuoteIdConverter;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $postData = $this->getRequest()->getPostValue();
        
        try {
            // 1. get quote_id
            $realQuoteId = $this->resolveQuoteId($postData);
            $this->logger->debug('test quote_id',["data"=>$realQuoteId]);
            // 2. get configurate fees
            $fees = $this->getShippingFeesConfig();
            
            // 3. calulate total fees
            $calculatedFees = $this->calculateTotalFees($postData, $fees);

            $this->logger->debug('fees',["data"=>$calculatedFees]);
            // 4-1. update quote address table
            $affectedRows = $this->updateQuoteAddress(
                $realQuoteId,
                $postData,
                $calculatedFees['total']
            );
            
            // 4-2. update quote table
            $affectedRows = $this->updateQuote(
                $realQuoteId,
                $postData,
                $calculatedFees['total']
            );

            // 5. return result
            return $result->setData([
                'success' => true,
                'affected_rows' => $affectedRows,
                'fee_breakdown' => $calculatedFees,
                'base_price' => $fees['base_price']
            ]);

        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage());
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            return $result->setData([
                'success' => false,
                'message' => __('An internal error occurred.')
            ]);
        }
    }

    /**
     * deal with Quote ID when customer logged in or not
     */
    protected function resolveQuoteId($postData)
    {
        if (empty($postData['quote_id'])) {
            throw new LocalizedException(__('Quote ID is required.'));
        }
        
        return is_numeric($postData['quote_id']) 
            ? $postData['quote_id']
            : $this->maskedQuoteIdConverter->execute($postData['quote_id']);
    }

    /**
     * get shipping fees config
     */
    protected function getShippingFeesConfig()
    {
        return [
            'base_price' => (float)$this->scopeConfig->getValue(
                self::CONFIG_PATH_BASE_PRICE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ) ?: 5.0, // 默认基础运费
            
            'residential' => (float)$this->scopeConfig->getValue(
                self::CONFIG_PATH_RESIDENTIAL_SURCHARGE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ) ?: 2.0,
            
            'scheduled' => (float)$this->scopeConfig->getValue(
                self::CONFIG_PATH_SCHEDULED_SURCHARGE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ) ?: 3.0
        ];
    }

    /**
     * calculate total fees
     */
    protected function calculateTotalFees($postData, $fees)
    {
        $isResidential = !empty($postData['is_residential']);
        $isScheduled = !empty($postData['is_scheduled']);

        return [
            'base' => $fees['base_price'],
            'residential' => $isResidential ? $fees['residential'] : 0,
            'scheduled' => $isScheduled ? $fees['scheduled'] : 0,
            'total' => $fees['base_price']
                      + ($isResidential ? $fees['residential'] : 0)
                      + ($isScheduled ? $fees['scheduled'] : 0)
        ];
    }

    /**
     * update quote_address table
     */
    protected function updateQuoteAddress($quoteId, $postData, $totalFee)
    {
        $connection = $this->resourceConnection->getConnection();
        
        return $connection->update(
            $this->resourceConnection->getTableName('quote_address'),
            [
                'brimar_residential' => !empty($postData['is_residential']) ? 1 : 0,
                'brimar_scheduled' => !empty($postData['is_scheduled']) ? 1 : 0,
                'brimar_shipping_fee' => $totalFee
            ],
            [
                'quote_id = ?' => $quoteId,
                'address_type = ?' => 'shipping'
            ]
        );
    }

        /**
     * update quote table
     */
    protected function updateQuote($quoteId, $postData, $totalFee)
    {
        $connection = $this->resourceConnection->getConnection();
        
        return $connection->update(
            $this->resourceConnection->getTableName('quote'),
            [
                'brimar_residential' => !empty($postData['is_residential']) ? 1 : 0,
                'brimar_scheduled' => !empty($postData['is_scheduled']) ? 1 : 0,
                'brimar_shipping_fee' => $totalFee
            ],
            [
                'entity_id = ?' => $quoteId,
            ]
        );
    }
}