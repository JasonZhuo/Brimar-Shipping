<?php
/**
 * Created By: jason Zhuo
 * 7/14/2025
 */

namespace Brimar\Shipping\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Psr\Log\LoggerInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;

class Brimar extends AbstractCarrier implements CarrierInterface
{
    protected $_code = 'brimar';
    protected $_isFixed = true;
    protected $rateResultFactory;
    protected $rateMethodFactory;
    protected $logger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        array $data = []
    ) {
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->logger = $logger;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        //$this->logger->debug('Brimar Shipping - Starting rate calculation');
        //$this->logger->debug('Request data:', ['request' => $request->getData()]);

        $result = $this->rateResultFactory->create();
        $method = $this->rateMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod($this->_code);
        $methodTitle= $this->getConfigData('name');
        //$method->setMethodTitle($this->getConfigData('name'));

        $amount = (float)$this->getConfigData('price');
        //$this->logger->debug('Base price: ' . $amount);

        // get the data from quote_address 
        $items = $request->getAllItems();
        if ($items && count($items)) {
            $quote = $items[0]->getQuote();
            $shippingAddress = $quote->getShippingAddress();
            
            // 获取数据库字段值
            $isResidential = (bool)$shippingAddress->getData('brimar_residential');
            $isScheduled = (bool)$shippingAddress->getData('brimar_scheduled');
            
            //$this->logger->debug('Database values:', [
            //    'brimar_residential' => $isResidential,
            //    'brimar_scheduled' => $isScheduled
            //]);

            if ($isResidential) {
                $amount += (float)$this->getConfigData('surcharge_residential');;
                $methodTitle.= ' + Residential ';
                //$this->logger->debug('Added residential surcharge: +2.00');
            }
            if ($isScheduled) {
                $amount += (float)$this->getConfigData('surcharge_scheduled');;
                $methodTitle.= ' + Scheduled ';
                //$this->logger->debug('Added scheduled surcharge: +3.00');
            }
        }
        $method->setMethodTitle($methodTitle);
        $method->setPrice($amount);
        $method->setCost($amount);

        //$this->logger->debug('Final calculated amount: ' . $amount);
        $result->append($method);

        return $result;
    }

    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }
}