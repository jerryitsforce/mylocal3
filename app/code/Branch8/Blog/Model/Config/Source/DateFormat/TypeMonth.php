<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Model\Config\Source\DateFormat;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class TypeMonth
 * @package Branch8\Blog\Model\Config\Source\DateFormat
 */
class TypeMonth implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $dateArray = [];
        $type = ['F , Y', 'Y - m', 'm / Y', 'M  Y'];
        foreach ($type as $item) {
            $dateArray [] = [
                'value' => $item,
                'label' => $item . ' (' . date($item) . ')'
            ];
        }

        return $dateArray;
    }
}
