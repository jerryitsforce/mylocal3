<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Ui\Component\Listing\Columns;

use Exception;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Security\Model\AdminSessionsManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class General extends Column
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
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    private $cacheBaseUrl = null;

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
        Image                      $imageHelper,
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
        $this->imageHelper = $imageHelper;
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
            case 'product_view':
                foreach ($dataSource['data']['items'] as &$item) {
                    if (isset($item[$fieldName]) && $item[$fieldName] != '') break;
                    $productId = $item['mageproduct_id'] ?? null;
                    $visibility = $item['visibility'] = (isset($item['visibility']) && $item['visibility'] != '') ? $item['visibility'] : $resourceProduct->getAttributeRawValue(
                        $productId,
                        'visibility',
                        $this->storeManager->getStore()->getId()
                    );
                    if ($productId && $visibility && (int)$visibility !== 1) {
                        $url = $this->getWebsiteUrl((int)$productId);
                        $sessionId = $this->urlEncoder->encode(
                            $this->adminSessionsManager->getCurrentSession()->getId()
                        );
                        $requestPath = $this->_data['config']['requestPath'] ?? '';
                        $item[$fieldName] = "<a href='" . $url . $requestPath . '/id/' . $productId . '/SID/' . $sessionId .
                            "/' target='blank' title='" . __('View Product') . "'>" . __('View') . '</a>';
                    } else {
                        $item[$fieldName] = __('N/A');
                    }
                }
                break;
            case 'product_name':
                foreach ($dataSource['data']['items'] as &$item) {
                    if (isset($item[$fieldName]) && $item[$fieldName] != '') break;
                    $productId = $item['mageproduct_id'] ?? null;
                    if (isset($item['product_name_item'])) {
                        $item[$fieldName] = $item['product_name_item'];
                    } else {
                        $item[$fieldName] = $item['product_name_item'] = $resourceProduct->getAttributeRawValue(
                            $productId,
                            'name',
                            $this->storeManager->getStore()->getId()
                        );
                    }
                    if (is_array($item[$fieldName])) {
                        $item[$fieldName] = reset($item[$fieldName]);
                    }
                    $item[$fieldName] = "<a href='".$this->urlBuilder
                            ->getUrl('catalog/product/edit', ['id' => $item['mageproduct_id']]).
                        "' target='blank' title='".__('View Product')."'>".$item[$fieldName].'</a>';
                }
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
            case 'product_price':
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
            case 'pro_prev':
                foreach ($dataSource['data']['items'] as &$item) {
                    if (isset($item[$fieldName . '_src']) && $item[$fieldName . '_src'] != '') break;
                    $store = $this->storeManager->getStore(
                        $this->context->getFilterParam('store_id', \Magento\Store\Model\Store::DEFAULT_STORE_ID)
                    );
                    $productId = $item['mageproduct_id'] ?? null;
                    $itemProduct = [];
                    $itemProduct['entity_id'] = $productId;
                    $itemProduct['thumbnail'] = $resourceProduct->getAttributeRawValue(
                        $productId,
                        'thumbnail',
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
                    if (isset($item['product_price']) && $item['product_price'] != '') {
                        $itemProduct['price'] = $item['product_price'];
                    } else {
                        $itemProduct['price'] = $item['product_price'] = $this->priceCurrency->format(
                            sprintf("%F", $resourceProduct->getAttributeRawValue(
                                $productId,
                                'price',
                                $this->storeManager->getStore()->getId()
                            )),
                            false,
                            PriceCurrencyInterface::DEFAULT_PRECISION,
                            $store
                        );
                    }
                    $product = new \Magento\Framework\DataObject($itemProduct);
                    $imageHelper = $this->imageHelper->init($product, 'product_listing_thumbnail');
                    $imageUrl = $imageHelper->getUrl();
                    $item[$fieldName . '_src'] = $imageUrl;
                    $item[$fieldName . '_alt'] = $imageHelper->getLabel();
                    $origImageHelper = $this->imageHelper->init(
                        $product,
                        'product_listing_thumbnail_preview'
                    );
                    $item[$fieldName . '_orig_src'] = $origImageHelper->getUrl();
                    $item[$fieldName . '_name'] = $product->getName();

                    $item[$fieldName . '_price'] = __('Price') . ' - ' . strip_tags(htmlspecialchars_decode($product->getPrice()));
                    $item[$fieldName . '_link'] = $this->urlBuilder->getUrl(
                        'catalog/product/edit',
                        ['id' => $productId, 'store' => 0]
                    );
                }
                break;
            default:
                $name = $fieldName;
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
}
