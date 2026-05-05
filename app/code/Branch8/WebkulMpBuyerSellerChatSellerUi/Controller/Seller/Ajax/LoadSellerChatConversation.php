<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatSellerUi\Controller\Seller\Ajax;

use Branch8\WebkulMpBuyerSellerChatSellerUi\Model\Actions\GetSellerChatConversations;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Webkul\SellerSubAccount\Helper\Data as HelperData;

/**
 * Class PostAttachment
 */
class LoadSellerChatConversation extends Action implements HttpGetActionInterface
{
    private HelperData $subAccountHelper;
    /**
     * @var GetSellerChatConversations
     */
    private GetSellerChatConversations $getSellerChatConversations;
    /**
     * @var \Magento\Customer\Model\Session
     */
    private \Magento\Customer\Model\Session $session;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @param Context $context
     * @param \Magento\Customer\Model\Session $session
     * @param HelperData $subAccountHelper
     * @param GetSellerChatConversations $getSellerChatConversations
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context                                          $context,
        \Magento\Customer\Model\Session                  $session,
        HelperData                                       $subAccountHelper,
        GetSellerChatConversations                       $getSellerChatConversations,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    )
    {
        parent::__construct($context);
        $this->subAccountHelper = $subAccountHelper;
        $this->getSellerChatConversations = $getSellerChatConversations;
        $this->session = $session;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     */
    public function execute()
    {
        $page = $this->_request->getParam('page');
        $conversations = [];
        $sellerId = (int)$this->session->getCustomerId();
        $subAccount = $this->subAccountHelper->getCurrentSubAccount();
        if ($subAccount->getId()) {
            $sellerId = (int)$subAccount->getSellerId();
        }
        if ($sellerId) {
            $conversations = $this->getSellerChatConversations->get($sellerId);
        }
        return $this->resultJsonFactory->create()->setData($conversations);
    }
}
