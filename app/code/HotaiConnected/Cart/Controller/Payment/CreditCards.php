<?php

declare(strict_types=1);

namespace HotaiConnected\Cart\Controller\Payment;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use HotaiConnected\Cart\Service\CreditCardService;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;

class CreditCards implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var CreditCardService
     */
    private CreditCardService $creditCardService;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param CustomerSession $customerSession
     * @param CreditCardService $creditCardService
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        CustomerSession $customerSession,
        CreditCardService $creditCardService,
        RequestInterface $request,
        LoggerInterface $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->creditCardService = $creditCardService;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        // 1. Check customer login
        if (!$this->customerSession->isLoggedIn()) {
            $result->setHttpResponseCode(401);
            return $result->setData([
                'success' => false,
                'message' => 'Customer not logged in.',
            ]);
        }

        // 2. Check hotai_token
        if (!$this->creditCardService->hasToken()) {
            return $result->setData([
                'success' => true,
                'cards' => [],
                'default_card_token_id' => null,
            ]);
        }

        // 3. Get card list
        try {
            $excludeExpired = (bool)$this->request->getParam('exclude_expired', false);
            $data = $this->creditCardService->getCardList($excludeExpired);

            return $result->setData([
                'success' => true,
                'cards' => $data['cards'],
                'default_card_token_id' => $data['default_card_token_id'],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[CreditCards API] Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $result->setHttpResponseCode(500);
            return $result->setData([
                'success' => false,
                'message' => 'Failed to retrieve credit card list.',
            ]);
        }
    }
}
