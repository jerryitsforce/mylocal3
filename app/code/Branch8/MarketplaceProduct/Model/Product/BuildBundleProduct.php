<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\Product;

use Magento\Bundle\Api\Data\LinkInterfaceFactory as LinkFactory;
use Magento\Bundle\Api\Data\OptionInterfaceFactory as OptionFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class BuildBundleProduct
{
    /**#@+
     * Constants for keys of product bundle.
     */
    public const BUNDLE_OPTIONS = 'bundle_options';
    public const BUNDLE_SELECTIONS = 'bundle_selections';
    public const AFFECT_BUNDLE_SELECTIONS = 'affect_bundle_product_selections';
    /**#@-*/

    /**
     * @var \Magento\Bundle\Model\Option
     */
    protected \Magento\Bundle\Model\Option $_optionMod;

    /**
     * @var ProductRepository
     */
    protected ProductRepository $_productRepository;

    /**
     * @var OptionFactory
     */
    protected OptionFactory $_optionFactory;

    /**
     * @var LinkFactory
     */
    protected LinkFactory $_linkFactory;

    /**
     * BuildBundleProduct constructor.
     *
     * @param \Magento\Bundle\Model\Option $optionMod
     * @param ProductRepository $productRepository
     * @param OptionFactory $optionFactory
     * @param LinkFactory $linkFactory
     */
    public function __construct(
        \Magento\Bundle\Model\Option $optionMod,
        ProductRepository $productRepository,
        OptionFactory $optionFactory,
        LinkFactory $linkFactory
    ) {
        $this->_optionMod = $optionMod;
        $this->_productRepository = $productRepository;
        $this->_optionFactory = $optionFactory;
        $this->_linkFactory = $linkFactory;
    }

    /**
     * Get the required keys for building a bundle product.
     *
     * @return string[]
     */
    public static function getRequiredKeys(): array
    {
        return [
            self::BUNDLE_OPTIONS,
            self::BUNDLE_SELECTIONS,
            self::AFFECT_BUNDLE_SELECTIONS
        ];
    }

    /**
     * Build configurable product.
     *
     * @param ProductInterface $product
     * @param array $productData
     *
     * @return void
     */
    public function execute(ProductInterface $product, array $productData): void
    {
        $compositeReadonly = $product->getCompositeReadonly();
        if (array_key_exists('bundle_options', $productData) && array_key_exists('bundle_selections', $productData)) {
            $productData['bundle_options'] = $this->removeEmptyOptions(
                $productData['bundle_options'],
                $productData['bundle_selections']
            );
            $bundleOptions['bundle_options'] = $productData['bundle_options'];
            $bundleSelections = $productData['bundle_selections'];
        }
        $collection = $this->_optionMod->getCollection()
            ->addFieldTofilter('parent_id', ['eq'=>$product->getId()]);
        if ($collection->getSize()) {
            $collection->walk('delete');
        }
        $i = 0;
        $l = 0;
        foreach ($bundleOptions['bundle_options'] as $key => $valueUp) {
            $bundleOptions['bundle_options'][$key]['bundle_selections'] = $bundleSelections[$key];
            $bundleOptions['bundle_options'][$key]['record_id'] = $l;
            foreach ($bundleSelections[$key] as $valueDown) {
                $bundleOptions['bundle_options'][$key]['bundle_button_proxy'][$i]['entity_id']
                    = $valueDown['product_id'];
                $i++;
            }
            $i = 0;
            $l++;
        }
        if (isset($bundleOptions['bundle_options'])) {
            $result = [];
            foreach ($bundleOptions['bundle_options'] as $key => $option) {
                if (empty($option['bundle_selections'])) {
                    continue;
                }
                $result['bundle_selections'][$key] = $option['bundle_selections'];
                unset($option['bundle_selections']);
                $result['bundle_options'][$key] = $option;
            }
            if ($result['bundle_selections'] && !$compositeReadonly) {
                $product->setBundleSelectionsData($result['bundle_selections']);
            }

            if ($result['bundle_options'] && !$compositeReadonly) {
                $product->setBundleOptionsData($result['bundle_options']);
            }
        }
        $bundleOptionsData = $product->getBundleOptionsData();
        if ($bundleOptionsData) {
            $options = [];
            $temp = [];
            foreach ($bundleOptionsData as $key => $dob) {
                if (isset($dob['delete']) && $dob['delete'] == 1) {
                    unset($bundleOptionsData[$key]);
                } else {
                    $dob['default_title'] = $dob['title'];
                    $temp[] = $dob;
                }
            }
            $bundleOptionsData = $temp;

            foreach ($bundleOptionsData as $key => $optionData) {
                if (isset($optionData['delete']) && (bool)$optionData['delete']) {
                    continue;
                }

                $option = $this->_optionFactory->create(['data' => $optionData]);
                $option->setSku($product->getSku());
                $option->setOptionId(null);

                $links = [];
                $bundleLinks = $product->getBundleSelectionsData();
                if (empty($bundleLinks[$key])) {
                    continue;
                }
                foreach ($bundleLinks[$key] as $linkData) {
                    if ((bool)$linkData['delete']) {
                        continue;
                    }
                    $links = $this->setQtySku(
                        $linkData,
                        $product,
                        $links
                    );
                }
                $option->setProductLinks($links);
                $options[] = $option;
            }
            $extension = $product->getExtensionAttributes();
            $extension->setBundleProductOptions($options);
            $product->setExtensionAttributes($extension);
            $affectProductSelections = (bool)$productData['affect_bundle_product_selections'] ?? false;
            $product->setCanSaveBundleSelections($affectProductSelections && !$compositeReadonly);
            $product->setHasOptions(1);
            $product->setRequiredOptions();
        }
    }

    /**
     * SetQtySKU
     *
     * @param array $linkData
     * @param \Magento\Catalog\Model\Product $product
     * @param array $links
     * @return array
     */
    public function setQtySku(
        $linkData,
        $product,
        $links
    ) {

        $link = $this->_linkFactory->create(['data' => $linkData]);

        if ((int)$product->getPriceType() !== \Magento\Bundle\Model\Product\Price::PRICE_TYPE_DYNAMIC) {
            if (array_key_exists('selection_price_value', $linkData)) {
                $link->setPrice($linkData['selection_price_value']);
            }
            if (array_key_exists('selection_price_type', $linkData)) {
                $link->setPriceType($linkData['selection_price_type']);
            }
        }
        $linkProduct = $this->_productRepository->getById($linkData['product_id']);
        $link->setSku($linkProduct->getSku());
        $link->setQty($linkData['selection_qty']);

        if (array_key_exists('selection_can_change_qty', $linkData)) {
            $link->setCanChangeQuantity($linkData['selection_can_change_qty']);
        }
        $links[] = $link;
        return $links;
    }

    /**
     * RemoveEmptyOptions
     *
     * @param array $bundleOptions
     * @param array $bundleSelections
     * @return array
     */
    public function removeEmptyOptions($bundleOptions, $bundleSelections)
    {
        foreach ($bundleOptions as $key => $bOption) {
            if (!array_key_exists($key, $bundleSelections)) {
                unset($bundleOptions[$key]);
            }
        }
        return $bundleOptions;
    }
}
