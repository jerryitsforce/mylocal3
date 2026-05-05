<?php

namespace Branch8\SellerContactInformation\Model\ResourceModel\ContractFiles;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Branch8\SellerContactInformation\Model\ContractFiles::class,
            \Branch8\SellerContactInformation\Model\ResourceModel\ContractFiles::class
        );
    }
}