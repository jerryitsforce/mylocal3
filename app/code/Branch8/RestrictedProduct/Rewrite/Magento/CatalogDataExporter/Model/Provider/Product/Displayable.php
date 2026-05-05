<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\RestrictedProduct\Rewrite\Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\DataExporter\Model\FailedItemsRegistry;

class Displayable extends \Magento\CatalogDataExporter\Model\Provider\Product\Displayable
{
    private FailedItemsRegistry $failedRegistry;

    /**
     * @param FailedItemsRegistry $failedRegistry
     */
    public function __construct(FailedItemsRegistry $failedRegistry)
    {
        $this->failedRegistry = $failedRegistry;
    }

    /**
     * Get provider data
     *
     * @param array $values
     * @return array
     */
    public function get(array $values) : array
    {
        $output = [];

        foreach ($values as $value) {
            try {
                $output[] = [
                    'productId' => $value['productId'],
                    'storeViewCode' => $value['storeViewCode'],
                    'displayable' => (
                        $value['status'] === 'Enabled'
                        && in_array($value['visibility'], ['Catalog', 'Search', 'Catalog, Search'])
                        && $value['hideProductOnSearch'] !== '1' // Check if product is not hidden on search
                    )
                ];
            } catch (\Throwable $e) {
                $this->failedRegistry->addFailed($value, $e);
            }
        }

        return $output;
    }
}