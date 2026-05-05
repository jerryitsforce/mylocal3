<?php
namespace HotaiConnected\Qware\Controller\Index;

use HotaiConnected\Qware\Helper\Api as QwareApi;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;

class GetVoucherInfo extends Action
{
    private QwareApi $qwareApi;
    private JsonFactory $jsonFactory;
    private LoggerInterface $logger;
    private FormKeyValidator $formKeyValidator;
    private CustomerSession $customerSession;
    private MarketplaceHelper $marketplaceHelper;

    public function __construct(
        Context $context,
        QwareApi $qwareApi,
        JsonFactory $jsonFactory,
        LoggerInterface $logger,
        FormKeyValidator $formKeyValidator,
        CustomerSession $customerSession,
        MarketplaceHelper $marketplaceHelper
    ) {
        parent::__construct($context);
        $this->qwareApi = $qwareApi;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
        $this->formKeyValidator = $formKeyValidator;
        $this->customerSession = $customerSession;
        $this->marketplaceHelper = $marketplaceHelper;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        
        // 檢查請求方法
        if (!$this->getRequest()->isPost()) {
            return $result->setHttpResponseCode(405)->setData([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
        }
        
        // 檢查是否為賣家 - 使用 Marketplace Helper
        /* if (!$this->marketplaceHelper->isSeller()) {
            return $result->setHttpResponseCode(403)->setData([
                'success' => false,
                'message' => 'Access denied - seller account required'
            ]);
        } */
        
        // 檢查 CSRF Token
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $result->setHttpResponseCode(403)->setData([
                'success' => false,
                'message' => 'Invalid form key'
            ]);
        }
        
        // 確認是 AJAX 請求
        if (!$this->getRequest()->isXmlHttpRequest()) {
            return $result->setHttpResponseCode(400)->setData([
                'success' => false,
                'message' => 'Invalid request type'
            ]);
        }
        
        try {
            $guid = $this->getRequest()->getParam('guid');
            
            if (empty($guid)) {
                return $result->setData([
                    'success' => false,
                    'message' => 'GUID is required'
                ]);
            }

            $response = $this->qwareApi->requestApiGetVoucherInfo($guid);
            
            if (isset($response['Data'])) {
                $data = $response['Data'];
                
                return $result->setData([
                    'success' => true,
                    'data' => [
                        // 必要欄位 - 對應前端更新需求
                        'name' => $data['Name'] ?? '',                          // Name商品名稱 => 產品名稱
                        'short_name' => $data['ShortName'] ?? '',               // ShortName商品簡稱 => 產品名稱-子名稱
                        'summary' => $data['Summary'] ?? '',                    // Summary商品簡述 => 產品簡介
                        'description' => $data['Description'] ?? '',            // Description商品說明 => 描述
                        'specification' => $data['Specification'] ?? '',        // Specification票券注意事項 => 描述
                        'feature' => $data['Feature'] ?? '',                    // Feature購回注意事項 => 注意事項
                        'notes' => $data['Notes'] ?? '',                        // Notes票券使用說明 => 注意事項
                        'max_purchase_qty' => $data['MaxPurchaseQty'] ?? 0,     // MaxPurchaseQty => 限直接結帳產品
                        'tags' => $data['Tags'] ?? '',                          // Tags商品標籤 => 搜尋標籤
                        
                        // 基本識別資訊
                        'id' => $data['Id'] ?? null,
                        'guid' => $data['Guid'] ?? '',
                        
                        // 銷售時間資訊 - 用於自動排程
                        'for_sale_start_date' => $data['ForSaleStartDate'] ?? '',
                        'for_sale_end_date' => $data['ForSaleEndDate'] ?? '',
                    ]
                ]);
            }

            return $result->setData([
                'success' => false,
                'message' => 'Invalid response from API'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Qware Frontend API Error: ' . $e->getMessage());
            
            return $result->setData([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}