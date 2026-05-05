<?php

namespace Branch8\Preorder\Ui\Component\Listing\Column;

class Price extends \Webkul\MarketplacePreorder\Ui\Component\Listing\Column\Price{
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $currencyCode = isset($item['base_currency_code']) ? $item['base_currency_code'] : null;
                $price = $item[$this->getData('name')] * $item['qty'];
                $item[$this->getData('name')] = $this->priceFormatter->format(
                    $price,
                    false,
                    2,
                    null,
                    $currencyCode
                );
            }
        }

        return $dataSource;
    }
}