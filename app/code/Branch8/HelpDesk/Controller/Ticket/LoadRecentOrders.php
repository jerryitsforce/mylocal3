<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Ticket;

use Branch8\HelpDesk\Api\Data\FindYourOrdersDataInterface;
use Branch8\HelpDesk\Api\Data\FindYourOrdersSearchResultInterface;
use Branch8\HelpDesk\Api\FindYourOrderServiceInterface;
use Branch8\HelpDesk\Model\FindYourOrderAction;
use Magento\Framework\Api\FilterFactory;
use Magento\Framework\Api\Search\FilterGroupFactory;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * Load Recent Orders
 */
class LoadRecentOrders implements HttpGetActionInterface
{
    private FindYourOrderAction $serviceFinder;
    private \Magento\Framework\App\Action\Context $context;
    private \Magento\Customer\Model\Session $customerSession;
    private \Magento\Framework\Json\Helper\Data $helper;
    private \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory;


    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param FindYourOrderAction $action
     */
    public function __construct(
        \Magento\Framework\App\Action\Context            $context,
        \Magento\Customer\Model\Session                  $customerSession,
        \Magento\Framework\Json\Helper\Data              $helper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        FindYourOrderAction                              $action
    )
    {
        $this->serviceFinder = $action;
        $this->context = $context;
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Execute
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Raw|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $page = (int)$this->context->getRequest()->getParam('page', 1);
        $limit = (int)$this->context->getRequest()->getParam('limit', 50);
        $q = $this->context->getRequest()->getParam('searchKey');
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        $response = [
            'options' => [],
            'total' => 0
        ];
        if (empty($this->customerSession->getCustomer()) || empty($q)) {
            return $resultJson->setData($response);
        }
        $searchResult = $this->serviceFinder->execute(
            $this->customerSession->getCustomer()->getId(),
            $limit,
            $page,
            $q
        );
        foreach ($searchResult->getItems() as $item) {
            $response['options'][] = [
                'value' => $item->getId(),
                'label' => $item->getIncrementId()
            ];
        }
        $response['total'] = $searchResult->getTotalCount();
        return $resultJson->setData($response);
    }
}
