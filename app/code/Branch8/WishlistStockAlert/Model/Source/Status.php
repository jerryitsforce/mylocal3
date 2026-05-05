<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       02/02/2026
 */

namespace Branch8\WishlistStockAlert\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    private $options;

    /**
     * @return array|array[]
     */
    public function toOptionArray()
    {
        if ($this->options) {
            return $this->options;
        }
        $this->options = [
            ['value' => '', 'label' => __('Please Select')],
            ['value' => 0, 'label' => __('Pending')],
            ['value' => 1, 'label' => __('Sent')],
            ['value' => 2, 'label' => __('Failed')],
        ];
        return $this->options;
    }
}
