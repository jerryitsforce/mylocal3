<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Model\Config\Source\Import;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Type
 * @package Branch8\Blog\Model\Config\Source\Import
 */
class Type implements ArrayInterface
{
    const WORDPRESS = "wordpress";
    const AHEADWORK = "aheadworksm1";
    const MAGEFAN = "magefan";

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->toArray() as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label
            ];
        }

        return $options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            "" => __('-- Please Select --'),
            self::WORDPRESS => __('Wordpress'),
            self::AHEADWORK => __('AheadWorks Blog [Magento 1]'),
            self::MAGEFAN => __('MageFan Blog [Magento 2]')
        ];
    }
}
