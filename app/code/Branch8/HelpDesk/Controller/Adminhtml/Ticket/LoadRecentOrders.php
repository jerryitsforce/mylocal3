<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Adminhtml\Ticket;

use Branch8\HelpDesk\Model\FindYourOrderActionInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * Load Recent Orders
 */
class LoadRecentOrders implements HttpGetActionInterface
{
    private FindYourOrderActionInterface $serviceFinder;
    private \Magento\Framework\App\Action\Context $context;
    private \Magento\Customer\Model\Session $customerSession;
    private \Magento\Framework\Json\Helper\Data $helper;
    private \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory;
    /**
     * @var mixed|string
     */
    private mixed $identifyKey;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param FindYourOrderActionInterface $action
     * @param $identifyKey
     */
    public function __construct(
        \Magento\Framework\App\Action\Context            $context,
        \Magento\Customer\Model\Session                  $customerSession,
        \Magento\Framework\Json\Helper\Data              $helper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        FindYourOrderActionInterface                     $action,
                                                         $identifyKey = 'entity_id',
    )
    {
        $this->identifyKey = $identifyKey;
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
        $isDefault = \filter_var($this->context->getRequest()->getParam('isDefault', false), FILTER_VALIDATE_BOOL);
        $customerId = $this->context->getRequest()->getParam('customerId', 0);
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        $response = [
            'options' => [],
            'total' => 0
        ];
        if (!$customerId) {
            return $resultJson->setData($response);
        }
        if ($q || $isDefault) {
            $searchResult = $this->serviceFinder->execute(
                $customerId,
                $limit,
                $page,
                $q
            );
            foreach ($searchResult->getItems() as $item) {
                $response['options'][] = [
                    'value' => $item->getData($this->identifyKey),
                    'label' => $item->getIncrementId()
                ];
            }
            $response['total'] = $searchResult->getTotalCount();
        }
        return $resultJson->setData($response);
    }
}
