<?php
namespace Branch8\Catalog\Plugin\Magento\Catalog\Api;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ProductRepositoryPlugin
{
    const ENABLE_SCHEDULE_ADMIN = 'branch8_catalog/schedule_setting/enable_backend';

    /**
     * @var MarketplaceHelper
     */
    protected $marketplaceHelper;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * ProductRepositoryPlugin constructor
     *
     * @param MarketplaceHelper $marketplaceHelper
     */
    public function __construct(
        MarketplaceHelper $marketplaceHelper,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->marketplaceHelper = $marketplaceHelper;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $entity
     * @return ProductInterface
     */
    public function afterGet(
        ProductRepositoryInterface $subject,
        ProductInterface $entity
    ) : ProductInterface
    {
        $product = $entity;
        /** Get Current Extension Attributes from Product */
        $sellerId = $this->marketplaceHelper->getSellerIdByProductId($product->getId());
        if ($sellerId) {
            $extensionAttributes = $product->getExtensionAttributes();
            $extensionAttributes->setSellerId($sellerId); // seller id field value set
            $product->setExtensionAttributes($extensionAttributes);
        }
        return $product;
    }

    /**
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $product
     * @param bool $saveOptions
     * @return array
     */
    public function beforeSave(
        ProductRepositoryInterface $subject,
        ProductInterface $product,
        $saveOptions = false
    ) : array
    {

        // Set SKU
        $sku = $product->getData('sku');
        if ($sku){
            if (str_contains($sku, 'HOTAI')) {
                $check = explode('-', $sku);
                if (strlen($check[0]) < 6) {
                    $sku = 'HOTAI' . time() . '-' . $sku;
                }
            } else {
                $sku = 'HOTAI' . time() . '-' . $sku;
            }
            $product->setData('sku', $sku);
        }
        // Check special price
        if($product->getData('special_price') !== null){
            if ($product->getData('special_price') > 0) {
                $specialPrice = $product->getData('special_price');
                if ($product->getData('cost_setting') == '1' || $product->getData('cost_setting') == 1) {
                    $product->unsetData('cost');
                    $commissionPercent = ($product->getData('commission_percent') && is_numeric(trim($product->getData('commission_percent')))) ? (float) trim($product->getData('commission_percent')) : 0;
                    $product->setData('cost', round((100 - $commissionPercent) / 100 * $specialPrice));
                } elseif ($product->getData('cost_setting') == '0' || $product->getData('cost_setting') == 0) {
                    $cost = ($product->getData('cost') && is_numeric(trim($product->getData('cost')))) ? (float) trim($product->getData('cost')) : 0;
                    $commissionPercent = ($specialPrice - $cost) / $specialPrice * 100;
                    // if ($commissionPercent > 0)
                    $commissionPercent = round($commissionPercent);
                    $product->unsetData('commission_percent');
                    $product->setData('commission_percent', $commissionPercent);
                }
            }
            if($this->_scopeConfig->getValue(self::ENABLE_SCHEDULE_ADMIN)){
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
        return [$product, $saveOptions];
    }

    /**
     * @param ProductRepositoryInterface $subject
     * @param ProductSearchResultsInterface $searchCriteria
     * @return ProductSearchResultsInterface
     */
    public function afterGetList(
        ProductRepositoryInterface $subject,
        ProductSearchResultsInterface $searchCriteria
    ) : ProductSearchResultsInterface
    {
        $products = [];
        foreach ($searchCriteria->getItems() as $entity) {
            /** Get Current Extension Attributes from Product */
            $sellerId = $this->marketplaceHelper->getSellerIdByProductId($entity->getId());
            if ($sellerId) {
                $extensionAttributes = $entity->getExtensionAttributes();
                $extensionAttributes->setSellerId($sellerId); // seller id field value set
                $entity->setExtensionAttributes($extensionAttributes);
            }
            $products[] = $entity;
        }
        $searchCriteria->setItems($products);
        return $searchCriteria;
    }
}
