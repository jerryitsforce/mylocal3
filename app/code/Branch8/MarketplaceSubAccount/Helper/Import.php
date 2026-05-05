<?php

namespace Branch8\MarketplaceSubAccount\Helper;

use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Framework\Exception\LocalizedException;
use Webkul\SellerSubAccount\Helper\Data as HelperData;
use Webkul\SellerSubAccount\Model\SubAccountFactory;

class Import extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var HelperData
     */
    protected $_helperData;
    /**
     * @var SubAccountFactory
     */
    protected $_subAccount;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;
    /**
     * @var Data
     */
    protected $b8SubAccountHelper;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param HelperData $helperData
     * @param SubAccountFactory $subAccount
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param Data $b8SubAccountHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        HelperData $helperData,
        SubAccountFactory $subAccount,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Branch8\MarketplaceSubAccount\Helper\Data $b8SubAccountHelper
    ){
        parent::__construct($context);
        $this->_helperData = $helperData;
        $this->_subAccount = $subAccount;
        $this->_date = $date;
        $this->b8SubAccountHelper = $b8SubAccountHelper;
    }

    public function importSubAccount($data){

        $sellerId = $data['seller']['seller_id'];
        try {
            $data = $this->validateData($data);

            $result = $this->_helperData->saveCustomerData($data['sub_account']);
            if (!empty($result['error']) && $result['error'] == 1) {
                return ['error' => true, 'message' => $result['message']];
            } else {
                $customerId = $result['customer_id'];
            }
            $value = $this->_subAccount->create();
            $value->setSellerId($sellerId);
            $value->setCustomerId($customerId);
            $value->setPermissionType(
                implode(',', $data['sub_account']['permission_type'])
            );
            $value->setStatus(1);//active
            $value->setCreatedDate($this->_date->gmtDate());
            $value->save();

//            $this->b8SubAccountHelper->sendMailNewSubAccount($customerId, EmailNotificationInterface::NEW_ACCOUNT_EMAIL_REGISTERED_NO_PASSWORD);

            return ['error' => false];
        } catch (\Exception $e) {
            return ['error' => true, 'message' => $e->getMessage()];
        }

    }


    public function validateData($postData)
    {
        if (!empty($postData['sub_account']['seller_id'])) {
            $sellerId = $postData['sub_account']['seller_id'];
            $customer = $this->_helperData->getCustomerById($sellerId);
            if (!$customer->getId()) {
                throw new LocalizedException(
                    __('Seller account does not exist.')
                );
            }
        } else {
            throw new LocalizedException(
                __('Please select valid master seller account.')
            );
        }
        if (empty($postData['sub_account']['permission_type'])) {
            $postData['sub_account']['permission_type'] = [];
        }
        return $postData;
    }
}