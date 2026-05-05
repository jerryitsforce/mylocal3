<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Ui\Component\Listing\Columns;

use Exception;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Security\Model\AdminSessionsManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;

class GeneralFront extends Column
{
    /**
     * @var EncoderInterface
     */
    private EncoderInterface $urlEncoder;

    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var AdminSessionsManager
     */
    private AdminSessionsManager $adminSessionsManager;

    /**
     * @var ProductFactory
     */
    private ProductFactory $productFactory;

    /**
     * @var UrlFinderInterface
     */
    protected $urlFinder;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var GetSalableQuantityDataBySku
     */
    private $getSalableQuantityDataBySku;

    /**
     * @var VariationsFactory
     */
    protected $variationsFactory;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    private $cacheBaseUrl = null;
    private $processedName = false;
    private $cacheVariantion = [];

    /**
     * ProductView constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param EncoderInterface $urlEncoder
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     * @param AdminSessionsManager $adminSessionsManager
     * @param ProductFactory $productFactory
     * @param UrlFinderInterface $urlFinder
     * @param ScopeConfigInterface $scopeConfig
     * @param Image $imageHelper
     * @param array $components
     * @param array $data
     * @param PriceCurrencyInterface|null $priceCurrency
     */
    public function __construct(
        ContextInterface           $context,
        UiComponentFactory         $uiComponentFactory,
        EncoderInterface           $urlEncoder,
        UrlInterface               $urlBuilder,
        StoreManagerInterface      $storeManager,
        AdminSessionsManager       $adminSessionsManager,
        ProductFactory             $productFactory,
        UrlFinderInterface         $urlFinder,
        ScopeConfigInterface       $scopeConfig,
        Image                      $imageHelper,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        VariationsFactory $variationsFactory,
        ResourceConnection $resourceConnection,
        array                      $components = [],
        array                      $data = [],
        ?PriceCurrencyInterface    $priceCurrency = null
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlEncoder = $urlEncoder;
        $this->urlBuilder = $urlBuilder;
        $this->storeManager = $storeManager;
        $this->adminSessionsManager = $adminSessionsManager;
        $this->productFactory = $productFactory;
        $this->urlFinder = $urlFinder;
        $this->scopeConfig = $scopeConfig;
        $this->imageHelper = $imageHelper;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->variationsFactory = $variationsFactory;
        $this->resourceConnection = $resourceConnection;
        $this->priceCurrency = $priceCurrency ?? ObjectManager::getInstance()
            ->get(PriceCurrencyInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        $fieldName = $this->getData('name');
        $resourceProduct = $this->productFactory->create()->getResource();
        switch ($fieldName) {
            case 'name':
                if ($this->processedName) break;
                foreach ($dataSource['data']['items'] as &$item) {
                    $productId = $item['mageproduct_id'] ?? null;
                    $storeId = $this->storeManager->getStore()->getId();
                    if (!isset($item[$fieldName]) || $item[$fieldName] == '') {
                        $item[$fieldName] = $item['product_name_item'] = $resourceProduct->getAttributeRawValue(
                            $productId,
                            $fieldName,
                            $storeId
                        );
                        if (is_array($item[$fieldName])) {
                            $item[$fieldName] = $item['product_name_item'] = reset($item[$fieldName]);
                        }
                    }
                    $visibility = $item['visibility'] = (isset($item['visibility']) && $item['visibility'] != '') ? $item['visibility'] : $resourceProduct->getAttributeRawValue(
                        $productId,
                        'visibility',
                        $storeId
                    );
                    $productStatus = $item['product_status'] = (isset($item['product_status']) && $item['product_status'] != '') ? $item['product_status'] : $resourceProduct->getAttributeRawValue(
                        $productId,
                        'status',
                        $storeId
                    );
                    if ($productId && $productStatus == 1 && $visibility !== 1) {
                        $url = $this->getWebsiteUrl((int)$productId);
                        $filterData = [
                            UrlRewrite::ENTITY_ID => $productId,
                            UrlRewrite::ENTITY_TYPE => \Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator::ENTITY_TYPE,
                            UrlRewrite::STORE_ID => $storeId,
                            UrlRewrite::REDIRECT_TYPE => 0
                        ];

                        $rewrite = $this->urlFinder->findOneByData($filterData);
                        $requestPath = $rewrite?->getRequestPath() ?? '';
                        $item[$fieldName] = "<a href='" . $url . $requestPath .
                            "' target='blank' title='" . __('View Product') . "'>" .$item[$fieldName] . '</a>';
                    }
                }
                $this->processedName = true;
                break;
            case 'commission_percent':
                foreach ($dataSource['data']['items'] as &$item) {
                    if (isset($item[$fieldName]) && $item[$fieldName] != '') break;
                    $productId = $item['mageproduct_id'] ?? null;
                    $item[$fieldName] = $resourceProduct->getAttributeRawValue(
                        $productId,
                        $fieldName,
                        $this->storeManager->getStore()->getId()
                    );
                    $item[$fieldName] = $item[$fieldName] ?? 0;
                }
                break;
            case 'special_price':
            case 'price':
            case 'cost':
                $store = $this->storeManager->getStore(
                    $this->context->getFilterParam('store_id', \Magento\Store\Model\Store::DEFAULT_STORE_ID)
                );

                $fieldName = $this->getData('name');
                foreach ($dataSource['data']['items'] as &$item) {
                    if (isset($item[$fieldName]) && $item[$fieldName] != '') {
                        $item[$fieldName] = $this->priceCurrency->format(
                            sprintf("%F", $item[$fieldName]),
                            false,
                            PriceCurrencyInterface::DEFAULT_PRECISION,
                            $store
                        );
                    } else {
                        $name = ($fieldName == 'product_price') ? 'price' : $fieldName;
                        $productId = $item['mageproduct_id'] ?? null;
                        $item[$fieldName] = $this->priceCurrency->format(
                            $resourceProduct->getAttributeRawValue(
                                $productId,
                                $name,
                                $this->storeManager->getStore()->getId()
                            ) ?? 0,
                            false,
                            PriceCurrencyInterface::DEFAULT_PRECISION,
                            $store
                        );
                    }
                }
                break;

            case 'thumbnail':
                foreach ($dataSource['data']['items'] as &$item) {
                    if (isset($item[$fieldName . '_src']) && $item[$fieldName . '_src'] != '') break;
                    $productId = $item['mageproduct_id'] ?? null;
                    $itemProduct = [];
                    $itemProduct['entity_id'] = $productId;
                    $itemProduct['thumbnail'] = $resourceProduct->getAttributeRawValue(
                        $productId,
                        'thumbnail',
                        $this->storeManager->getStore()->getId()
                    );
                    $itemProduct['image'] = $resourceProduct->getAttributeRawValue(
                        $productId,
                        'image',
                        $this->storeManager->getStore()->getId()
                    );
                    if (isset($item['product_name_item']) && $item['product_name_item'] != '') {
                        $itemProduct['name'] = $item['product_name_item'];
                    } else {
                        $itemProduct['name'] = $item['product_name_item'] = $resourceProduct->getAttributeRawValue(
                            $productId,
                            'name',
                            $this->storeManager->getStore()->getId()
                        );
                    }
                    $product = new \Magento\Framework\DataObject($itemProduct);
                    $imageHelper = $this->imageHelper->init($product, 'product_thumbnail_image');
                    $item[$fieldName . '_src'] = $imageHelper->getUrl();
                    $item[$fieldName . '_alt'] = $imageHelper->getLabel();
                    $item[$fieldName . '_link'] = $this->urlBuilder->getUrl(
                        'marketplace/product/edit',
                        ['id' => $product->getEntityId()]
                    );
                    $origImageHelper = $this->imageHelper->init($product, 'product_base_image');
                    $item[$fieldName . '_orig_src'] = $origImageHelper->getUrl();
                }
                break;
            case 'salable_quantity':
            case 'reserved_quantity':
                foreach ($dataSource['data']['items'] as &$row) {
                    if (isset($row[$fieldName]) && $row[$fieldName] != '') break;
                    $row['salable_quantity'] =
                        $this->isSourceItemManagementAllowedForProductType->execute($row['type_id']) === true
                            ? $this->getSalableQuantityItemData($row['sku'], $row['mage_pro_row_id'])
                            : [];
                    $row['reserved_quantity'] = $this->getReservedQuantityItemData($row['sku'], $row['mage_pro_row_id']);
                }
                break;
            default:
                $name = $fieldName;
                if ($fieldName == 'user_updated') $name = 'admin_user_updated';
                if ($fieldName == 'product_status') $name = 'status';
                foreach ($dataSource['data']['items'] as &$item) {
                    if (isset($item[$fieldName]) && $item[$fieldName] != '') break;
                    $productId = $item['mageproduct_id'] ?? null;
                    $item[$fieldName] = $resourceProduct->getAttributeRawValue(
                        $productId,
                        $name,
                        $this->storeManager->getStore()->getId()
                    );
                }
        }

        return $dataSource;
    }

    /**
     * Get website URL by product ID.
     *
     * @param int $productId
     *
     * @return string
     */
    private function getWebsiteUrl(int $productId): string
    {
        if (!empty($this->cacheBaseUrl)) {
            return $this->cacheBaseUrl;
        }
        try {
            $website = $this->storeManager->getWebsite(1);
            $stores = $website->getStores();

            if (!empty($stores)) {
                $store = reset($stores);
                $this->cacheBaseUrl = $store->getBaseUrl();
            }
            return $this->cacheBaseUrl;
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Get salable quantity data for product
     *
     * @param string $sku
     * @return array
     */
    private function getReservedQuantityItemData(string $sku, string $row_id)
    {
        $sku = htmlspecialchars_decode($sku, ENT_QUOTES | ENT_SUBSTITUTE);

        $variationData = $this->getVariationStock($row_id);
        if (count($variationData)) {
            $data = [];
            foreach ($variationData as $key => $value) {
                $data[] = '<span><strong>' . $value['comb'] . '</strong>:<span>' . $value['ready_to_ship_qty'] . '</span></span>';
            }
            return  '<div style="width: 100px;">'.implode('<br>', $data).'</div>';
        }

        return $this->getReservedQuantityBySku($sku);
    }

    /**
     * Get salable quantity data for product
     *
     * @param string $sku
     * @return array
     */
    private function getSalableQuantityItemData(string $sku, string $row_id)
    {
        $sku = htmlspecialchars_decode($sku, ENT_QUOTES | ENT_SUBSTITUTE);

        $variationStock = $this->getVariationStock($row_id);
        if (count($variationStock)) {
            $data = [];
            foreach ($variationStock as $key => $value) {
                $data[] = '<span><strong>' . $value['comb'] . '</strong>:<span>' . $value['stock'] . '</span></span>';
            }
            return  '<div style="width: 100px;">'.implode('<br>', $data).'</div>';
        }

        $qty = 0;
        $salableQuantityData = $this->getSalableQuantityDataBySku->execute($sku);
        if(isset($salableQuantityData[0]['qty'])){
            $qty = $salableQuantityData[0]['qty'];
        }
        return $qty;
    }

    private function getReservedQuantityBySku(string $sku)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('inventory_reservation');

        $select = $connection->select()
            ->from(['ir' => $tableName], [])
            ->columns(['reserved_qty' => new \Zend_Db_Expr('SUM(ir.quantity)')]) // Sum the quantity
            ->where('ir.sku = ?', $sku)
            ->group('ir.sku');

        $reservedQty = $connection->fetchOne($select);

        return $reservedQty !== false ? (abs((int)$reservedQty)) : 0;
    }

    private function getVariationStock($row_id)
    {
        if (isset($this->cacheVariantion[$row_id])) {
            return $this->cacheVariantion[$row_id];
        }
        $this->cacheVariantion[$row_id] = [];
        $collection = $this->variationsFactory->create()
            ->getCollection()
            ->addFieldToFilter("product_id", $row_id);
        if ($collection->getSize()) {
            $this->cacheVariantion[$row_id] = $collection->getData();
        }
        return $this->cacheVariantion[$row_id];
    }
}
