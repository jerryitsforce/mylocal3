<?php

declare(strict_types=1);

namespace Branch8\Report\Observer;

use Branch8\Report\Helper\Data as DataHelper;
use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;

class ProductChangeLogBeforeObserver implements ObserverInterface
{
    /**#@+
     * Constants for keys of data array.
     */
    public const PRODUCT_LINKS = 'product_links';
    public const OPTION_VARIATIONS = 'option_variations';
    /**#@-*/

    /**
     * @var array
     */
    private static array $productLinks = [];

    /**
     * @var array
     */
    private static array $optionVariations = [];

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var DataHelper
     */
    private DataHelper $dataHelper;

    /**
     * Constructor.
     *
     * @param Registry $registry
     * @param DataHelper $dataHelper
     */
    public function __construct(
        Registry   $registry,
        DataHelper $dataHelper
    ) {
        $this->registry = $registry;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Returns data.
     *
     * @param int $productId
     * @param string $type
     *
     * @return array
     */
    public static function getData(int $productId, string $type): array
    {
        return match ($type) {
            self::PRODUCT_LINKS => self::$productLinks[$productId] ?? [],
            self::OPTION_VARIATIONS => self::$optionVariations[$productId] ?? [],
            default => [],
        };
    }

    /**
     * Clear data.
     *
     * @param int $productId
     * @param string $type
     *
     * @return void
     */
    public static function clearData(int $productId, string $type): void
    {
        switch ($type) {
            case self::PRODUCT_LINKS:
                unset(self::$productLinks[$productId]);
                break;
            case self::OPTION_VARIATIONS:
                unset(self::$optionVariations[$productId]);
                break;
            default:
                break;
        }
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        /** @var Product $product */
        $product = $observer->getProduct();
        if (!$product || !$product->getId()) {
            return;
        }
        $links = [];
        $request = $this->dataHelper->getRequest();
        $requestAction = $request->getActionName();
        if (in_array($requestAction, ['approve', 'massApprove'])) {
            $productLinksBeforeApprove = $this->registry->registry('current_product_links_before_approve');
            if ($productLinksBeforeApprove === false) {
                self::$productLinks[$product->getId()] = ['related_skus' => [], 'upsell_skus' => [], 'crosssell_skus' => []];
                return;
            } else {
                $links = is_array($productLinksBeforeApprove) ? $productLinksBeforeApprove : [];
            }
        }

        $productLinks = $this->dataHelper->prepareProductLinks($product, $links);
        self::$productLinks[$product->getId()] = $productLinks;

        $options = $product->getOptions();
        if ($options) {
            $optionVariations = [];
            foreach ($options as $option) {
                /** @var ProductCustomOptionInterface $optionValues */
                $optionValues = $option->getValues() ?: $option->getData('values');
                if (empty($optionValues)) {
                    continue;
                }
                $rowId = (int)$option->getProductId();
                $variations = $this->dataHelper->getOptionVariations()->execute($rowId);
                if (!empty($variations)) {
                    $optionVariations = array_merge($optionVariations, $variations);
                }
            }
            self::$optionVariations[$product->getId()] = $optionVariations ?: ['__EMPTY__'];
        }
    }
}
