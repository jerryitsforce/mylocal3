<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Branch8\AdvancedPermissions\Plugin\WebkulMarketplace\Block\Adminhtml\Customer;

use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Exception\LocalizedException;
use Webkul\Marketplace\Helper\Data;
use Webkul\Marketplace\Model\SellerFactory;

class Edit
{
    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $_helper;

    /**
     * @var CustomerFactory
     */
    protected $customerModel;

    /**
     * @var SellerFactory
     */
    protected $sellerModel;

    /**
     * Construct
     *
     * @param Data $helper
     * @param CustomerFactory|null $customerModel
     * @param SellerFactory|null $sellerModel
     */
    public function __construct(
        \Webkul\Marketplace\Helper\Data $helper,
        CustomerFactory $customerModel = null,
        SellerFactory $sellerModel = null
    ) {
        $this->_helper = $helper;
        $this->customerModel = $customerModel ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(CustomerFactory::class);
        $this->sellerModel = $sellerModel ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(SellerFactory::class);
    }

    /**
     * Get seller info collection
     *
     * @return array
     * @throws LocalizedException
     */
    public function aroundGetSellerInfoCollection(\Webkul\Marketplace\Block\Adminhtml\Customer\Edit $subject, \Closure $proceed)
    {
        $customerId = $subject->getRequest()->getParam('id');
        $requestParams = $subject->getRequest()->getParams();
        $storeId = (int)$subject->getRequest()->getParam('store', 0);
        $data = [];
        if ($customerId != '') {
            $user = $this->customerModel->create()->load($customerId);
            if (!isset($requestParams['store'])) {
                $storeId = $user->getStoreId();
            }
            $collection = $this->sellerModel->create()
                ->getCollection()
                ->addFieldToFilter('seller_id', $customerId)
                ->addFieldToFilter('store_id', $storeId);
            if (!count($collection)) {
                $collection = $this->sellerModel->create()
                    ->getCollection()
                    ->addFieldToFilter('seller_id', $customerId)
                    ->addFieldToFilter('store_id', 0);
            }
            $name = explode(' ', $user->getName());
            $bannerpic = '';
            $logopic = '';
            $countrylogopic = '';
            foreach ($collection as $record) {
                $data = $record->getData();
                $bannerpic = $record->getBannerPic() ? $record->getBannerPic() :'' ;
                $logopic = $record->getLogoPic() ? $record->getLogoPic() : '';
                $countrylogopic = $record->getCountryPic() ? $record->getCountryPic() :"";
                if (strlen($bannerpic) <= 0) {
                    $bannerpic = $this->_helper->getProfileBannerImage();
                }
                if (strlen($logopic) <= 0) {
                    $logopic = 'noimage.png';
                }
                if (strlen($countrylogopic) <= 0) {
                    $countrylogopic = '';
                }
            }
            $data['firstname'] = $name[0];
            $data['lastname'] = $name[1];
            $data['email'] = $user->getEmail();
            $data['banner_pic'] = $bannerpic;
            $data['logo_pic'] = $logopic;
            $data['country_pic'] = $countrylogopic;
        }

        return $data;
    }
}
