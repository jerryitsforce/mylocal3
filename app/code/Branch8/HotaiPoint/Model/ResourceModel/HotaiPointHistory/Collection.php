<?php

namespace Branch8\HotaiPoint\Model\ResourceModel\HotaiPointHistory;

use Magento\Framework\Data\Collection as MagentoCollection;

class Collection extends MagentoCollection
{
    public function setSize($size)
    {
        $this->_totalRecords = (int) $size;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSize()
    {
        return (int) $this->_totalRecords;
    }
}
