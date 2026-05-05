<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       27/01/2026
 */

namespace Branch8\WishlistStockAlert\Model\ProcessQueueType;

interface ProcessQueueTypeInterface
{
    /**
     * @param array $job
     * @return mixed
     */
    public function execute(array $job);
}
