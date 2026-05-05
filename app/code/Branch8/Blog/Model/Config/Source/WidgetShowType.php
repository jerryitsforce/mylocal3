<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class ShowType
 * @package Branch8\Blog\Model\Config\Source\Widget
 */
class WidgetShowType implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'new', 'label' => __('New')],
            ['value' => 'category', 'label' => __('Category')]
        ];
    }
}
