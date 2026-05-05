<?php

namespace Branch8\CustomNotification\Model\OneId;

use Magento\Framework\DataObject;

interface ReadHandler
{
    /**
     * @param string $path
     * @return  DataObject
     */
    public function read(string $path);
}
