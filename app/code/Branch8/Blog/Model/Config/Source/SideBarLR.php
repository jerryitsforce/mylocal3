<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class SideBarLR
 * @package Branch8\Blog\Model\Config\Source
 */
class SideBarLR implements ArrayInterface
{
    const LEFT = '2columns-left';
    const RIGHT = '2columns-right';

    /**
     * Options getter
     *
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
            self::LEFT => __('Left'),
            self::RIGHT => __('Right')
        ];
    }
}
