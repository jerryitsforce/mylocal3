<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Cart\Controller\Data;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\Controller\Result\JsonFactory;
use HotaiConnected\Cart\Service\UnifiedCartDataService;
use Psr\Log\LoggerInterface;

class Index implements HttpGetActionInterface, CsrfAwareActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var UnifiedCartDataService
     */
    private $unifiedCartDataService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param UnifiedCartDataService $unifiedCartDataService
     * @param LoggerInterface $logger
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        UnifiedCartDataService $unifiedCartDataService,
        LoggerInterface $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->unifiedCartDataService = $unifiedCartDataService;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $data = $this->unifiedCartDataService->getUnifiedCartData();
            $result->setData($data);
        } catch (\Exception $e) {
            $this->logger->critical('Error fetching unified cart data', ['exception' => $e]);
            $result->setHttpResponseCode(500);
            $result->setData([
                'error' => true,
                'message' => __('An error occurred. Please try again later.')->render()
            ]);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
