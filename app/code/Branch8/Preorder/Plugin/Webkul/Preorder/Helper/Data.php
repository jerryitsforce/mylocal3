<?php

namespace Branch8\Preorder\Plugin\Webkul\Preorder\Helper;

use Branch8\Preorder\Model\IsPreorderProduct;
use Branch8\Preorder\Model\Source\PreorderMode;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Branch8\Preorder\Helper\Data as B8PreorderHelperData;
use Psr\Log\LoggerInterface;

class Data
{
    /**
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $_storeManager;

    /**
     * @var Configurable
     */
    private Configurable $_configurable;

    /**
     * @var B8PreorderHelperData
     */
    protected B8PreorderHelperData $b8PreorderHelperData;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    protected $_productFactory;

    protected $isPreorder = [];

    private LoggerInterface $logger;
    /**
     * @var IsPreorderProduct
     */
    private IsPreorderProduct $isPreorderProduct;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param Configurable $configurable
     * @param B8PreorderHelperData $b8PreorderHelperData
     * @param ResourceConnection $resourceConnection
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param IsPreorderProduct $isPreorderProduct
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     */
    public function __construct(
        ProductRepositoryInterface            $productRepository,
        StoreManagerInterface                 $storeManager,
        Configurable                          $configurable,
        B8PreorderHelperData                  $b8PreorderHelperData,
        ResourceConnection                    $resourceConnection,
        ScopeConfigInterface                  $scopeConfig,
        LoggerInterface                       $logger,
        IsPreorderProduct $isPreorderProduct,
        \Magento\Catalog\Model\ProductFactory $productFactory
    )
    {
        $this->productRepository = $productRepository;
        $this->_storeManager = $storeManager;
        $this->_configurable = $configurable;
        $this->b8PreorderHelperData = $b8PreorderHelperData;
        $this->resourceConnection = $resourceConnection;
        $this->scopeConfig = $scopeConfig;
        $this->_productFactory = $productFactory;
        $this->logger = $logger;
        $this->isPreorderProduct = $isPreorderProduct;
    }

    /**
     * Is Product is set to Preorder|Not
     * @param string $productId
     * @return boolean
     */
    public function aroundIsPreorder($subject, callable $proceed, $productId = '')
    {
        return $this->checkIsPreorder((int)$productId);
    }

    /**
     * Check cart item qty && pre-order maximum qty,
     * only check for The start/end date modes
     * @param $subject
     * @param callable $proceed
     * @param $item
     * @param $product
     * @return bool
     */
    public function aroundGetQtyCheck($subject, callable $proceed, $item, $product)
    {
        $productType = $product->getTypeId();
        if ($productType == 'configurable') {
            $configModel = $this->_configurable;
            $usedProductIds = $configModel->getUsedProductIds($product);
            foreach ($usedProductIds as $usedProductId) {
                if ($subject->isPreorder($usedProductId)) {
                    $product = $subject->usedProductIdPreorder($usedProductId);
                    if ($product->getPreorderMode() == PreorderMode::START_END_DATE
                        && (int)$product->getPreorderUseQty() == 1) {
                        $preorderQty = (int)$product->getWkMppreorderQty();
                        if ($preorderQty < $item->getQty()) {
                            return false;
                        }
                    }
                }
            }
        } else {
            try {
                $product = $this->productRepository->getById($product->getId());
                $preorderQty = (int)$product->getWkMppreorderQty();
                if ($product->getPreorderMode() == PreorderMode::START_END_DATE
                    && (int)$product->getPreorderUseQty() == 1
                    && $preorderQty < $item->getQty()) {
                    return false;
                }
            } catch (\Exception $e) {
                return true;
            }
        }

        return true;
    }

    /**
     * Override Core function
     * @param $subject
     * @param callable $proceed
     * @param $productId
     * @return string
     */
    public function aroundGetPreOrderInfoBlock($subject, callable $proceed, $productId)
    {
        return $this->b8PreorderHelperData->getPdpMessage($productId);
    }


    /**
     * Check Configurable Product is Preorder or Not.
     *
     * @param int $productId
     *
     * @return bool
     */
    public function aroundIsConfigPreorder($subject, callable $proceed, $productId)
    {
        try {
            $product = $this->productRepository->getById($productId);
            $isProduct = true;
        } catch (\Exception $e) {
            $isProduct = false;
        }
        if ($isProduct) {
            $productType = $product->getTypeId();
            if ($productType == 'configurable') {
                $configModel = $this->_configurable;
                $usedProductIds = $configModel->getUsedProductIdsConfig($product);
                foreach ($usedProductIds as $usedProductId) {
                    if ($subject->isPreorder($usedProductId)) {
                        return true;
                    }
                }
            } elseif ($productType == 'bundle') {
                $selectionCollection = $product->getTypeInstance(true)
                    ->getSelectionsCollection(
                        $product->getTypeInstance(true)->getOptionsIds($product),
                        $product
                    );
                foreach ($selectionCollection as $_product) {
                    $isPreorder = $subject->isPreorder($_product->getId());
                    if ($isPreorder) {
                        return true;
                    }
                }
            } elseif ($productType == 'grouped') {
                $groupChildren = $product->getTypeInstance()->getAssociatedProducts($product);
                foreach ($groupChildren as $_product) {
                    $isPreorder = $subject->isPreorder($_product->getId());
                    if ($isPreorder) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Override core ext, core ext does not allow bundle, grouped on pre-order
     * @param $subject
     * @param callable $proceed
     * @param string $productId
     * @return bool
     */
    public function aroundIsChildProduct($subject, callable $proceed, $productId = ''): bool
    {
        return false;
    }

    /**
     * Always enable the qty field in add/ edit, using preorder_use_qty attribute to control the mppreorder_qty for every product,
     * instead of for all products of seller
     * @param $subject
     * @param callable $proceed
     * @return bool
     */
    public function getPreorderQtyEnable($subject, callable $proceed): bool
    {
        $configuration = $subject->getSellerConfiguration();
        if (!empty($configuration)) {
            if ($configuration['mppreorder_qty'] == 1) {
                return true;
            }
        } else {
            return true;
        }

        return false;
    }

    /**
     * getSellerPreorderSpecification used to get buyer preorder configuration,
     * We modify the core function, we set default to all user can buy the preorder,
     * if the config preorder_specific is not set, the default valus is all users.
     * It(preorder_specific) can be 0 if seller set, null or 1 are the same(all user)
     * @param $subject
     * @param callable $proceed
     * @param int $sellerId
     * @return boolean
     */
    public function getSellerPreorderSpecification($subject, callable $proceed, $sellerId): bool
    {
        if ((int)$sellerId !== 0 && $sellerId !== null && $sellerId !== "") {
            $configuration = $subject->getSellerConfiguration($sellerId);
            if (!empty($configuration)) {
                if ($configuration['preorder_specific'] === 0) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            $specification = $subject->getConfigData('mppreorder_specific');
            if ((int)$specification === 0) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * @param int $productId
     * @return bool
     */
    public function checkIsPreorder(int $productId)
    {
        return $this->isPreorderProduct->checkIsPreorder($productId);
    }

    public function afterGetStockDetails($subject, $result, $productId)
    {
        $product = $this->productRepository->getById($productId);
        $result['preorder'] = [
            'mode' => $product->getPreorderMode(),
            'preorder_use_qty' => $product->getPreorderUseQty(),
            'preorder_qty' => $product->getWkMppreorderQty()
        ];
        return $result;
    }

    public function aroundGetPreorderCompleteProductId($subject, $result)
    {
        $preorderCompleteProductSku = $this->scopeConfig->getValue('mppreorder/general_setting/preorder_conplete_product_sku');
        $id = $this->_productFactory->create()->getIdBySku($preorderCompleteProductSku);
        return $id;
    }


}
