<?php
namespace Branch8\Catalog\Plugin\Magento\Catalog\Controller\Adminhtml\Product\Action\Attribute;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Catalog\Controller\Adminhtml\Product\Action\Attribute\Save as ProductActionAttributeSave;
use Magento\Customer\Model\CustomerFactory;
use Magento\User\Model\UserFactory;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Webkul\SellerSubAccount\Helper\Data as SellerSubAccountHelper;

class Save
{
    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var AuthSession
     */
    private AuthSession $authSession;

    /**
     * @var MarketplaceHelper
     */
    private MarketplaceHelper $marketplaceHelper;

    /**
     * @var SellerSubAccountHelper
     */
    private SellerSubAccountHelper $sellerSubAccountHelper;

    /**
     * @var UserFactory
     */
    protected UserFactory $userFactory;

    /**
     * @var CustomerFactory
     */
    protected CustomerFactory $customerModel;

    /**
     * @param UserContextInterface $userContext
     * @param AuthSession $authSession
     * @param MarketplaceHelper $marketplaceHelper
     * @param SellerSubAccountHelper $sellerSubAccountHelper
     * @param UserFactory $userFactory
     * @param CustomerFactory $customerModel
     */
    public function __construct(
        UserContextInterface $userContext,
        AuthSession $authSession,
        MarketplaceHelper $marketplaceHelper,
        SellerSubAccountHelper $sellerSubAccountHelper,
        UserFactory $userFactory,
        CustomerFactory $customerModel
    ) {
        $this->userContext = $userContext;
        $this->authSession = $authSession;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->sellerSubAccountHelper = $sellerSubAccountHelper;
        $this->userFactory = $userFactory;
        $this->customerModel = $customerModel;
    }

    /**
     * Update product attributes
     */
    public function beforeExecute(ProductActionAttributeSave $subject)
    {
        $adminUpdatedUser = $this->getUpdatedByUser();
        if (!$adminUpdatedUser) {
            return;
        }
        foreach (['attributes', 'inventory'] as $paramName) {
            $data = $subject->getRequest()->getParam($paramName, []);
            if (!empty($data)) {
                $data['admin_user_updated'] = $adminUpdatedUser;
                $subject->getRequest()->setParam($paramName, $data);
            }
        }
    }

    /**
     * Get the user who updated the data.
     *
     * @return string
     */
    private function getUpdatedByUser(): string
    {
        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_ADMIN) {
            $userId = $this->userContext->getUserId();
            if ($userId) {
                return $this->userFactory->create()->load($userId)->getUserName();
            }
        }
        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER) {
            $userId = $this->userContext->getUserId();
            if ($userId) {
                $customer = $this->customerModel->create()->load($userId);
                return $customer->getData('prefix') . " " . $customer->getFirstname() . ' ' . $customer->getLastname();
            }
        }

        $sellerName = $this->authSession->getUser()?->getUserName();
        if (!empty($sellerName)) {
            return $sellerName;
        }

        $isPartner = $this->marketplaceHelper->isSeller();
        if ($isPartner == 1) {
            $sellerId = $this->sellerSubAccountHelper->getCustomerId();
            if (!$sellerId) {
                $sellerId = $this->marketplaceHelper->getCustomerId();
            }
            $customer = $this->customerModel->create()->load($sellerId);
            return $customer->getData('prefix') . " " . $customer->getFirstname() . ' ' . $customer->getLastname();
        }

        return '';
    }
}
