<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       01/04/2026
 */

namespace Branch8\WishlistStockAlert\Plugin\Magento\Wishlist\Model\ResourceModel\Item;

class CollectionPlugin
{
    private $isJoined = false;

    /**
     * @param \Magento\Wishlist\Model\ResourceModel\Item\Collection $subject
     * @param ...$arguments
     * @return array
     */
    public function beforeLoad(\Magento\Wishlist\Model\ResourceModel\Item\Collection $subject, ...$arguments)
    {
        if ($this->isJoined) {
            return $arguments;
        }
        $from = $subject->getSelect()->getPart(\Zend_Db_Select::FROM);
        if (isset($from['alert'])) {
            $this->isJoined = true;
            return $arguments;
        }
        $subject->getSelect()->join(['alert' => 'branch8_wishlist_stock_alert'],
            'alert.wishlist_item_id=main_table.wishlist_item_id',
            ['combo']
        );
        $this->isJoined = true;
        return $arguments;
    }
}
