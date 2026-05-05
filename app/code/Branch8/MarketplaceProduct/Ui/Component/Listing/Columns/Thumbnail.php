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

namespace Branch8\MarketplaceProduct\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Catalog\Model\ProductFactory;

class Thumbnail extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    protected $localeCurrency;
    /**
     * @var Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var ProductFactory
     */
    protected $productModel;



    /**
     * Construct
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param ProductFactory $productModel
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ProductFactory $productModel,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->localeCurrency = $localeCurrency;
        $this->storeManager = $storeManager;
        $this->imageHelper = $imageHelper;
        $this->urlBuilder = $urlBuilder;
        $this->productModel = $productModel;
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $store = $this->storeManager->getStore(
            $this->context->getFilterParam(
                'store_id',
                \Magento\Store\Model\Store::DEFAULT_STORE_ID
            )
        );
        $currency = $this->localeCurrency->getCurrency($store->getBaseCurrencyCode());
        $resourceProduct = $this->productModel->create()->getResource();
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                $thumbnailAfter = null;
                $additionalInformationData = $item['additional_information'];
                if ($additionalInformationData != null) {
                    $additionalInformation = json_decode((string)$additionalInformationData, true);
                    $thumbnail = $additionalInformation['thumbnail'] ?? null;
                    $thumbnailAfter = $thumbnail['after'] ?? null;
                }
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'tmp/catalog/product/';
                if (isset($item['is_new_product']) && $item['is_new_product']) {
                    $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product/';
                }
                $itemProduct = [];
                $productId = $item['mageproduct_id'];
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
                    $itemProduct['price'] = $item['product_price'] = $resourceProduct->getAttributeRawValue(
                        $productId,
                        'price',
                        $this->storeManager->getStore()->getId()
                    );
                }
                $product = new \Magento\Framework\DataObject($itemProduct);
                //$product = new \Magento\Framework\DataObject($item);
                $imageHelper = $this->imageHelper->init($product, 'product_listing_thumbnail');
                $imageUrl = $imageHelper->getUrl();
                $item[$fieldName.'_src'] = $thumbnailAfter ? $mediaUrl.$thumbnailAfter  : $imageUrl;
                $item[$fieldName.'_alt'] = $imageHelper->getLabel();
                $price = $currency->toCurrency(sprintf('%f', $product->getPrice()));
                $origImageHelper = $this->imageHelper->init(
                    $product,
                    'product_listing_thumbnail_preview'
                );
                $item[$fieldName.'_orig_src'] = $thumbnailAfter ? $mediaUrl.$thumbnailAfter  : $origImageHelper->getUrl();
                $item[$fieldName.'_name'] = $product->getName();

                $item[$fieldName . '_price'] = __('Price') . ' - ' . strip_tags(htmlspecialchars_decode($price));
                $item[$fieldName . '_link'] = $this->urlBuilder->getUrl(
                    'catalog/product/edit',
                    ['id' => $productId, 'store' => 0]
                );
            }
        }

        return $dataSource;
    }

}
