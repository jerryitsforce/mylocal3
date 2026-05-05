<?php
/**
 * Branch8 Catalog Observer
 *
 * @category    Branch8
 * @package     Branch8_Catalog
 * @author      Branch8
 */
declare(strict_types=1);

namespace Branch8\Catalog\Observer\Adminhtml;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class RemoveSpecialToDateBeforeSave
 * Removes the special_to_date value before saving the product in admin.
 */
class RemoveSpecialToDateBeforeSave implements ObserverInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var ProductInterface $product */
        $product = $observer->getEvent()->getProduct();
        if ($product instanceof ProductInterface) {
            if($product->getData('special_price') !== null){
                if($this->_scopeConfig->getValue('branch8_catalog/schedule_setting/enable_backend')) {
                    if($product->getData('special_to_date') !== null){
                        $special_to_date = strtotime($product->getData('special_to_date'));
                        if($special_to_date < strtotime("now")){
                            $product->setData('special_from_date', null);
                            $product->setData('special_to_date', null);
                        }
                    } else {
                        if($product->getData('special_from_date') !== null){
                            $product->setData('special_from_date', null);
                            $product->setData('special_to_date', null);
                        }
                    }

                } else {
                    $product->setData('special_from_date', null);
                    $product->setData('special_to_date', null);
                }
            }
        }
    }
}
