<?php declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatCustomerUi\Model\Api;

use Branch8\WebkulMpBuyerSellerChat\Api\ChatConversationRepositoryInterface;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatConversation;
use Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatConversationFactory;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileEntity;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\BuildBuyerSellerConversationData;
use Branch8\WebkulMpBuyerSellerChat\Model\Actions\GetOrCreateChatProfile;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatRole;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatStatus;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Customer\Model\ResourceModel\Visitor\CollectionFactory as VisitorCollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Visitor;
use Webkul\MpBuyerSellerChat\Model\CustomerDataRepository;

class CustomerLoadChatProfile implements \Branch8\WebkulMpBuyerSellerChatCustomerUi\Api\CustomerLoadChatProfileInterface
{

    private Session $session;
    private ChatConversationFactory $chatConversationFactory;
    private \Branch8\WebkulMpBuyerSellerChatCustomerUi\Model\Actions\CustomerChatProfileData $customerChatProfileData;

    /**
     * @param Session $session
     * @param \Branch8\WebkulMpBuyerSellerChatCustomerUi\Model\Actions\CustomerChatProfileData $customerChatProfileData
     */
    public function __construct(
        Session                                                                          $session,
        \Branch8\WebkulMpBuyerSellerChatCustomerUi\Model\Actions\CustomerChatProfileData $customerChatProfileData
    )
    {
        $this->session = $session;
        $this->customerChatProfileData = $customerChatProfileData;
    }

    /**
     * @return false|mixed|string
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function load()
    {
        $customerId = $this->session->getCustomerId();
        $chatProfileData = $this->customerChatProfileData->getByCustomerId(
            (int)$customerId
        );
        return json_encode($chatProfileData);
    }
}
