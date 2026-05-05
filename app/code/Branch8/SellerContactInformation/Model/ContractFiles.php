<?php

namespace Branch8\SellerContactInformation\Model;

class ContractFiles extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Branch8\SellerContactInformation\Model\ResourceModel\ContractFiles::class);
    }
}