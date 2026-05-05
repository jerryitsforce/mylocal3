<?php

namespace Branch8\Catalog\Plugin;

use Magento\CatalogImportExport\Model\Import\Product;
use Magento\Framework\Exception\LocalizedException;

class ProductImportValidator
{
    public function beforeValidateRow(
        Product $subject,
        array $rowData,
                $rowNum
    ) {
        if (isset($rowData['weight'])) {
            $weight = (float)$rowData['weight'];
            if ($weight <= 0) {
                throw new LocalizedException(
                    __('Row %1: "weight" must be greater than 0.', $rowNum)
                );
            }
        }
        return [$rowData, $rowNum];
    }
}
