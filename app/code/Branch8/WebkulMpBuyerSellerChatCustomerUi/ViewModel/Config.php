<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatCustomerUi\ViewModel;

use Branch8\WebkulMpBuyerSellerChatCustomerUi\Model\Actions\CustomerChatProfileData;
use Branch8\WebkulMpBuyerSellerChatCustomerUi\Model\Actions\GetCustomerChatConversations;
use Branch8\WebkulMpBuyerSellerChat\Model\GeneralConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 *
 */
class Config implements ArgumentInterface
{
    private GeneralConfig $configData;
    private UrlInterface $urlBuilder;
    private GetCustomerChatConversations $getCustomerSellerChatData;
    private \Magento\Customer\Model\Session $session;
    private CustomerChatProfileData $customerChatProfileData;
    private RequestInterface $request;

    /**
     * @param \Magento\Customer\Model\Session $session
     * @param GeneralConfig $configData
     * @param UrlInterface $urlBuilder
     * @param GetCustomerChatConversations $getCustomerSellerChatData
     * @param CustomerChatProfileData $customerChatProfileData
     * @param RequestInterface $request
     */
    public function __construct(
        \Magento\Customer\Model\Session $session,
        GeneralConfig                   $configData,
        UrlInterface                    $urlBuilder,
        GetCustomerChatConversations    $getCustomerSellerChatData,
        CustomerChatProfileData         $customerChatProfileData,
        RequestInterface $request
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->configData = $configData;
        $this->getCustomerSellerChatData = $getCustomerSellerChatData;
        $this->session = $session;
        $this->customerChatProfileData = $customerChatProfileData;
        $this->request = $request;
    }

    /**
     * @param $customerId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomerSellerChatData()
    {
        if (!$this->session->getCustomerId()) {
            return;
        }
        return $this->getCustomerSellerChatData->get($this->session->getCustomerId());
    }

    /**
     * @return null
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCustomerChatProfile()
    {
        if (!$this->session->getCustomerId()) {
            return;
        }
        return $this->customerChatProfileData->getByCustomerId(
            (int)$this->session->getCustomerId()
        );
    }

    /**
     * @return array
     */
    public function getGeneralConfig()
    {
        $generalConfig = $this->configData->getGeneralConfig();
        $generalConfig['openChat'] = (bool)$this->request->getParam('openChat', false);
        return $generalConfig;
    }
    /**
     * @return array
     */
    public function getChangeProfileUploadConfig()
    {
        return [
            'url' => $this->urlBuilder->getUrl('mpchatsystem/chat/ChangeProfileImage'),
            'useProfileUrl' => $this->urlBuilder->getUrl('mpchatsystem/chat/UseProfileImage')
        ];
    }
}
