<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Rma\Controller\Order;

use Branch8\HotaiPoint\Helper\Api as HotaiPointApi;
use Magento\Framework\Controller\Result\JsonFactory;

class CheckPoints extends \Magento\Framework\App\Action\Action
{
    /**
     * Log option value for check points controller.
     */
    private const LOG_OPTION = 'CheckPoints';
    /**
     * @var HotaiPointApi
     */
    protected $hotaiPointApi;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param HotaiPointApi $hotaiPointApi
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        HotaiPointApi $hotaiPointApi
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->hotaiPointApi = $hotaiPointApi;
        parent::__construct($context);
    }
    /**
     * Rma Items Action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $info = [];

        try {
            $data = $this->_request->getParams();

            // $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/return_fails.log');
            // $logger = new \Zend_Log();
            // $logger->addWriter($writer);
            // $logger->info(print_r(json_encode($data), true));

            $itemId = $this->_request->getParam('item_id');

            // Get Point info
            $returnFailurePointInfo = $this->hotaiPointApi->getReturnFailurePointInfoByOrderItemId((int) $itemId);

            // $logger->info(print_r(json_decode($returnFailurePointInfo), true));

            // Example: Process the returned point info
            if ($returnFailurePointInfo && isset($returnFailurePointInfo['failurePoint']) && $returnFailurePointInfo['failurePoint'] > 0) {
                $info['data']['failure_point'] = true;
                $info['data']['points'] = $returnFailurePointInfo['failurePoint'];
            } else {
                $info['data']['failure_point'] = false;
            }

            $info['success'] = true;
            $info['message'] = __('');
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__);
            $info = [
                'success' => false,
                'message' => __('Something went wrong. Please try again later.')
            ];
        }
        
        // Set return json info
        $result->setData($info);
        return $result;
    }
}
