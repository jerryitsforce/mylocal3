<?php
namespace Branch8\HotaiAuth\Model\Config\Backend\Serialized;

class ArraySerialized extends \Magento\Config\Model\Config\Backend\Serialized\ArraySerialized
{
    /**
     * Unset array element with '__empty' key
     *
     * @return $this
     */
    public function beforeSave()
    {
        $value = $this->getValue();

        if (is_array($value)) {
            unset($value['__empty']);
        }

        if (empty($value)) {
            $value = $this->getOldValue();
        }

        $this->setValue($value);
        return parent::beforeSave();
    }
}
