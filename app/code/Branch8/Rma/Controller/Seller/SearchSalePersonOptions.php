<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Rma\Controller\Seller;

use Branch8\Rma\Model\Actions\GetSalePersonOptions;
use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Controller getting the asset options for multiselect filter
 */
class SearchSalePersonOptions extends Action implements HttpGetActionInterface
{
    private const HTTP_OK = 200;
    private const HTTP_INTERNAL_ERROR = 500;
    private const HTTP_BAD_REQUEST = 400;
    private const LOG_OPTION = 'SearchSalePersonOptions';

    private $logger;
    private GetSalePersonOptions $getSalePersonOptions;

    /**
     * @param GetSalePersonOptions $getSellerOptions
     * @param Context $context
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetSalePersonOptions $getSellerOptions,
        Context              $context,
        LoggerInterface      $logger
    )
    {
        parent::__construct($context);
        $this->getSalePersonOptions = $getSellerOptions;
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
        if (!$searchKey) {
            return $resultJson->setData([
                'options' => [],
                'total' => 0
            ]);
        }

        try {
            $filters[] = ['field' => 'role_name LIKE (?)', 'value' => '%' . $searchKey . '%'];
            $responseContent = $this->getSalePersonOptions->get($filters, $pageNum, $limit);
            $responseCode = self::HTTP_OK;
        } catch (LocalizedException $exception) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($exception, self::LOG_OPTION, __METHOD__);
            $responseCode = self::HTTP_BAD_REQUEST;
            $responseContent = [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        } catch (Exception $exception) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($exception, self::LOG_OPTION, __METHOD__);
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
