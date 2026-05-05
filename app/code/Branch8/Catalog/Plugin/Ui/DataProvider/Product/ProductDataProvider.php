<?php

namespace Branch8\Catalog\Plugin\Ui\DataProvider\Product;

use Branch8\Catalog\Model\Source\HiddenType;
use Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider as BaseProductDataProvider;
use Magento\Framework\Api\Filter;
use Magento\Framework\App\RequestInterface;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;

class ProductDataProvider
{
    private $alreadyProcessed = false;
    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var VariationsFactory
     */
    protected $variationsFactory;

    /**
     * Constructor
     *
     * @param RequestInterface $request
     * @param VariationsFactory $variationsFactory
     */
    public function __construct(
        RequestInterface $request,
        VariationsFactory $variationsFactory
    ) {
        $this->request = $request;
        $this->variationsFactory = $variationsFactory;
    }

    /**
     * @param BaseProductDataProvider $subject
     * @param $result
     * @return mixed
     */
    public function afterGetCollection(BaseProductDataProvider $subject,\Magento\Catalog\Ui\DataProvider\Product\ProductCollection $result)
    {
        if ($this->alreadyProcessed) {
            return $result;
        }
        $filterData = $this->request->getParam('filters');
        if ($subject->getName() == 'product_listing_data_source' && !isset($filterData['is_hidden'])) {
            $result->addFieldToFilter('is_hidden', ['eq' => HiddenType::NOT_HIDDEN]);
        }

        // Ensure 'first_enabled_date' is loaded
        // Ensure 'seller_shop_name' is loaded for product listing
        // Ensure 'special_from_date' and 'special_to_date' are loaded
        if ($subject->getName() == 'product_listing_data_source') {
            $result->addAttributeToSelect(['seller_shop_name', 'special_from_date', 'special_to_date']);
            $storeId = (int)$this->request->getParam('store', 0);

            // Force join attributes to ensure they are loaded
            if (method_exists($result, 'joinAttribute') && !$result->getFlag('b8_added_custom_joins')) {
                if ($storeId === 0) {
                    $result->joinAttribute('b8_special_price', 'catalog_product/special_price', 'entity_id', null, 'left', 0);
                    $result->joinAttribute('b8_cost', 'catalog_product/cost', 'entity_id', null, 'left', 0);
                } else {
                    // Start of fallback logic
                    $result->joinAttribute('b8_special_price_def', 'catalog_product/special_price', 'entity_id', null, 'left', 0);
                    $result->joinAttribute('b8_cost_def', 'catalog_product/cost', 'entity_id', null, 'left', 0);

                    $result->joinAttribute('b8_special_price_curr', 'catalog_product/special_price', 'entity_id', null, 'left', $storeId);
                    $result->joinAttribute('b8_cost_curr', 'catalog_product/cost', 'entity_id', null, 'left', $storeId);
                }

                $result->setFlag('b8_added_custom_joins', true);
            } elseif (!method_exists($result, 'joinAttribute')) {
                 $result->addAttributeToSelect(['special_price', 'cost']);
            }
        }
        $this->alreadyProcessed = true;
        return $result;
    }

    /**
     * Adjust the filter.
     *
     * @param BaseProductDataProvider $subject
     * @param callable $proceed
     * @param Filter $filter
     *
     * @return void
     */
    public function aroundAddFilter(BaseProductDataProvider $subject, callable $proceed, Filter $filter): void
    {
        if ($filter->getField() == 'hotai_brand') {
            $subject->getCollection()->addFieldToFilter('brand', ['in' => $filter->getValue()]);
        } else {
            $proceed($filter);
        }
    }

    /**
     * @param BaseProductDataProvider $subject
     * @param array $result
     * @return array
     */
    public function afterGetData(BaseProductDataProvider $subject, array $result)
    {
        if ($subject->getName() !== 'product_listing_data_source') {
            return $result;
        }

        $storeId = (int)$this->request->getParam('store', 0);

        if (isset($result['items'])) {
            foreach ($result['items'] as &$item) {
                if ($storeId === 0) {
                    if (isset($item['b8_special_price'])) {
                        $item['special_price'] = $item['b8_special_price'];
                    }
                    if (isset($item['b8_cost'])) {
                        $item['cost'] = $item['b8_cost'];
                    }
                } else {
                    // Fallback logic for Special Price
                    if (isset($item['b8_special_price_curr']) && $item['b8_special_price_curr'] !== null) {
                         $item['special_price'] = $item['b8_special_price_curr'];
                    } elseif (isset($item['b8_special_price_def'])) {
                         $item['special_price'] = $item['b8_special_price_def'];
                    }

                    // Fallback logic for Cost
                     if (isset($item['b8_cost_curr']) && $item['b8_cost_curr'] !== null) {
                         $item['cost'] = $item['b8_cost_curr'];
                    } elseif (isset($item['b8_cost_def'])) {
                         $item['cost'] = $item['b8_cost_def'];
                    }
                }
            }
        }

        if (empty($result['items'])) {
            return $result;
        }

        $rowIds = [];
        foreach ($result['items'] as $item2) {
            if (isset($item2['row_id'])) {
                $rowIds[] = $item2['row_id'];
            } elseif (isset($item2['mage_pro_row_id'])) {
                $rowIds[] = $item2['mage_pro_row_id'];
            }
        }

        if (empty($rowIds)) {
            return $result;
        }

        $variationsData = $this->getVariationsData($rowIds);

        foreach ($result['items'] as &$item3) {
            $rowId = $item3['row_id'] ?? $item3['mage_pro_row_id'] ?? null;
            if ($rowId && isset($variationsData[$rowId])) {
                $item3['branch8_variations'] = $variationsData[$rowId];
            } else {
                $item3['branch8_variations'] = [];
            }
        }

        return $result;
    }

    /**
     * @param array $rowIds
     * @return array
     */
    private function getVariationsData(array $rowIds)
    {
        $collection = $this->variationsFactory->create()->getCollection();
        $collection->addFieldToFilter('product_id', ['in' => $rowIds]);

        $data = [];
        foreach ($collection as $variation) {
            $productId = $variation->getProductId();
            if (!isset($data[$productId])) {
                $data[$productId] = [];
            }
            $data[$productId][] = $variation->getData();
        }

        return $data;
    }
}
