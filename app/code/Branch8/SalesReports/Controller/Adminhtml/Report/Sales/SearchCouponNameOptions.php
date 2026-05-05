<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\SalesReports\Controller\Adminhtml\Report\Sales;

use Branch8\SalesReports\Model\Actions\GetCouponNameOptions;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Branch8\SalesReports\Helper\Logger as CustomLogger;

/**
 * Controller getting the asset options for multiselect filter
 */
class SearchCouponNameOptions extends Action implements HttpGetActionInterface
{
    private const HTTP_OK = 200;
    private const HTTP_INTERNAL_ERROR = 500;
    private const HTTP_BAD_REQUEST = 400;

    private CustomLogger $logger;

    private GetCouponNameOptions $getOptions;

    /**
     * @param GetCouponNameOptions $getSellerOptions
     * @param Context $context
     * @param CustomLogger $logger
     */
    public function __construct(
        GetCouponNameOptions $getSellerOptions,
        Context          $context,
        CustomLogger     $logger
    )
    {
        parent::__construct($context);
        $this->getOptions = $getSellerOptions;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $searchKey = $this->getRequest()->getParam('searchKey');
        $limit = $this->getRequest()->getParam('limit');
        $pageNum = $this->getRequest()->getParam('page');
        try {
            $filters = [];
            if ($searchKey) {
                $filters[] = ['field' => 'rule_name LIKE (?)', 'value' => '%' . $searchKey . '%'];
            }
            $responseContent = $this->getOptions->get($filters, $pageNum, $limit);
            $responseCode = self::HTTP_OK;
        } catch (LocalizedException $exception) {
            $responseCode = self::HTTP_BAD_REQUEST;
            $responseContent = [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        } catch (Exception $exception) {
            $this->logger->critical($exception);
            $responseCode = self::HTTP_INTERNAL_ERROR;
            $responseContent = [
                'success' => false,
                'message' => __('An error occurred on attempt to get details.'),
            ];
        }

        $resultJson->setHttpResponseCode($responseCode);
        $resultJson->setData($responseContent);

        return $resultJson;
    }
}
