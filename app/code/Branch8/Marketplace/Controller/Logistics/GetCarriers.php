<?php
/**
 * Branch8 Marketplace
 * Get Carriers with Connection Status
 */

namespace Branch8\Marketplace\Controller\Logistics;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Branch8\Sales\Helper\Config as B8SalesConfig;
use HotaiConnected\Logistics\Model\ResourceModel\LogisticsSettings\CollectionFactory as LogisticsSettingsCollectionFactory;
use Branch8\Marketplace\Service\MarketplaceLogger;
use Magento\Framework\App\ObjectManager;

class GetCarriers extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var B8SalesConfig
     */
    protected $b8SalesConfig;

    /**
     * @var LogisticsSettingsCollectionFactory
     */
    protected $logisticsSettingsCollectionFactory;

    /**
     * @var MarketplaceLogger
     */
    private MarketplaceLogger $marketplaceLogger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CustomerSession $customerSession
     * @param B8SalesConfig $b8SalesConfig
     * @param LogisticsSettingsCollectionFactory $logisticsSettingsCollectionFactory
     * @param MarketplaceLogger|null $marketplaceLogger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CustomerSession $customerSession,
        B8SalesConfig $b8SalesConfig,
        LogisticsSettingsCollectionFactory $logisticsSettingsCollectionFactory,
        MarketplaceLogger $marketplaceLogger = null
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->b8SalesConfig = $b8SalesConfig;
        $this->logisticsSettingsCollectionFactory = $logisticsSettingsCollectionFactory;
        $this->marketplaceLogger = $marketplaceLogger
            ?: ObjectManager::getInstance()->get(MarketplaceLogger::class);
    }

    /**
     * Execute
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $carriers = $this->getCarriersWithStatus();

            return $result->setData([
                'success' => true,
                'carriers' => $carriers
            ]);
        } catch (\Exception $e) {
            $this->marketplaceLogger->logException('GetCarriers', $e);
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get carriers with connection status
     * Reuse the same logic from Branch8\Marketplace\Block\Order\Items
     *
     * @return array
     */
    protected function getCarriersWithStatus()
    {
        $carriers = $this->b8SalesConfig->getLogisticsCompanies();
        $sellerId = $this->customerSession->getCustomerId();

        if (!$sellerId) {
            // Return carriers without status
            $result = [];
            foreach ($carriers as $code => $url) {
                $result[$code] = [
                    'url' => $url,
                    'label' => $code,
                    'connected' => false
                ];
            }
            return $result;
        }

        // Query logistics settings for this seller
        $collection = $this->logisticsSettingsCollectionFactory->create();
        $collection->addFieldToFilter('seller_id', $sellerId)
                   ->addFieldToFilter('is_active', 1);

        // Get connected carrier names
        $connectedCarriers = [];
        foreach ($collection as $setting) {
            $connectedCarriers[] = $setting->getLogisticsCompanyName();
        }

        // Build result array with status labels
        $result = [];
        foreach ($carriers as $code => $url) {
            $result[$code] = [
                'url' => $url,
                'label' => in_array($code, $connectedCarriers) ? $code . ' (已串接)' : $code,
                'connected' => in_array($code, $connectedCarriers)
            ];
        }

        return $result;
    }
}
