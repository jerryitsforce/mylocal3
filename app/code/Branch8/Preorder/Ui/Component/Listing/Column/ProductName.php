<?php

namespace Branch8\Preorder\Ui\Component\Listing\Column;

class ProductName extends \Webkul\MarketplacePreorder\Ui\Component\Listing\Column\ProductName{
    
    public function prepareDataSource(array $dataSource){
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['order_id'])) {
                    $prodName = $this->productloader->create()->load($item['product_id'])->getName();
                    $item['product_name'] = "<a href='".$this->urlBuilder->getUrl(
                            'catalog/product/edit',
                            ['id' => $item['product_id']]
                        )."' 
                        target='blank' 
                        title='".__('View Product')."'>".$prodName.'</a>';
                }
            }
        }
        return $dataSource;
    }
}