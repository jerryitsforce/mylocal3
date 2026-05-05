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

namespace Branch8\MarketplaceStaging\Controller\Product;

/**
 * Webkul Marketplace Product Builder Controller Class.
 */
class Builder
{
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_helper;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $_state;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\Registry           $registry
     * @param \Webkul\Marketplace\Helper\Data       $helper
     * @param \Magento\Framework\App\State          $state
     * @param \Psr\Log\LoggerInterface              $loggerInterface
     */
    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Registry $registry,
        \Webkul\Marketplace\Helper\Data $helper,
        \Magento\Framework\App\State $state,
        \Psr\Log\LoggerInterface $loggerInterface
    ) {
        $this->_productFactory = $productFactory;
        $this->_logger = $loggerInterface;
        $this->_helper = $helper;
        $this->_state = $state;
        $this->_registry = $registry;
    }

    /**
     * Build product based on requestData.
     *
     * @param array $requestData
     * @param int $store
     *
     * @return \Magento\Catalog\Model\Product $mageProduct
     */
    public function build($requestData, $store = 0)
    {
        if (!empty($requestData['id'])) {
            $mageProductId = (int) $requestData['id'];
        } else {
            $mageProductId = '';
        }
        /** @var $mageProduct \Magento\Catalog\Model\Product */
        $mageProduct = $this->_productFactory->create();
        if (!empty($requestData['set'])) {
            $mageProduct->setAttributeSetId($requestData['set']);
        }
        if (!empty($requestData['type'])) {
            $mageProduct->setTypeId($requestData['type']);
        }
        $mageProduct->setStoreId($store);
        if ($mageProductId) {
            try {
                if ($this->_state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
                    $mageProduct->load($mageProductId);
                } else {
                    $isPartner = $this->_helper->isSeller();
                    $flag = false;
                    if ($isPartner == 1) {
                        $rightseller = $this->_helper->isRightSeller($mageProductId);
                        if ($rightseller) {
                            $flag = true;
                        }
                    }
                    if ($flag) {
                        $mageProduct->load($mageProductId);
                    }
                }
            } catch (\Exception $e) {
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketplaceStaging', 'mplog')){
                    $this->_helper->logDataInLogger(
                        "Controller_Product_Builder execute : ".$e->getMessage()
                    );
                }
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketplaceStaging', 'exceptionlog')){
                    $this->_logger->critical($e);
                }
            }
        }
        if (!$this->_registry->registry('product')) {
            $this->_registry->register('product', $mageProduct);
        }
        if (!$this->_registry->registry('current_product')) {
            $this->_registry->register('current_product', $mageProduct);
        }
        return $mageProduct;
    }
}
