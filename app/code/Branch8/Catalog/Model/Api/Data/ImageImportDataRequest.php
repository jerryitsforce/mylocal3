<?php

namespace Branch8\Catalog\Model\Api\Data;

use Magento\Framework\DataObject;

class ImageImportDataRequest extends DataObject implements \Branch8\Catalog\Api\Data\ImageImportDataInterface
{
    /**
     * @param $file
     * @return $this|mixed
     */
    public function setFile($file)
    {
        $this->setData(self::_FILE, $file);
        return $this;
    }

    /**
     * @return string
     */
    public function getFile():string
    {
        return $this->getData(self::_FILE);
    }

    /**
     * @param $type
     * @return $this|mixed
     */
    public function setType($type)
    {
        $this->setData(self::_TYPE, $type);
        return $this;
    }

    /**
     * @return string
     */
    public function getType():string
    {
        return $this->getData(self::_TYPE);
    }

}