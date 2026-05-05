<?php
declare(strict_types=1);

namespace Branch8\HotaiCore\Ui\Component\Listing\Filters\Options;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\App\ResourceConnection;

class SellerShopName implements OptionSourceInterface
{
    private \Webkul\Marketplace\Helper\Data $helper;
    private ResourceConnection $resource;

    public function __construct(
        \Webkul\Marketplace\Helper\Data $helper,
        ResourceConnection $resource
    )
    {
        $this->helper = $helper;
        $this->resource = $resource;
    }

    public function toOptionArray(): array
    {
        $options = [];
        
        $connection = $this->resource->getConnection();
        $tableName = $connection->getTableName('marketplace_userdata');
        
        $select = $connection->select()
            ->from($tableName, ['seller_id', 'shop_title'])
            ->where('shop_title IS NOT NULL')
            ->where('shop_title != ""');
        
        $sellers = $connection->fetchAll($select);
        
        foreach($sellers as $seller){
            if(!empty($seller['shop_title'])){
                $options[] = [
                    'value' => $seller['shop_title'],
                    'label' => $seller['shop_title']
                ];
            }
        }
        
        // 去重
        $uniqueOptions = [];
        foreach ($options as $option) {
            $uniqueOptions[$option['value']] = $option;
        }
        
        return array_values($uniqueOptions);
    }
}