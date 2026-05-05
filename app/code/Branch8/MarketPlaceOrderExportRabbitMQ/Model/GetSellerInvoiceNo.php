<?php

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model;

use Magento\Framework\App\ResourceConnection;

class GetSellerInvoiceNo
{
    private ResourceConnection $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @param $sellerId
     * @return string
     */
    public function execute($sellerId)
    {
        $select = $this->resource->getConnection()->select()
            ->from('marketplace_userdata', ['invoice_company_no'])
            ->where('seller_id = ?', $sellerId);
        return $this->resource->getConnection()->fetchOne($select);
    }
}
