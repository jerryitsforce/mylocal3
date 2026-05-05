<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       27/01/2026
 */

namespace Branch8\WishlistStockAlert\Model\ProcessQueueType;

use Branch8\WishlistStockAlert\Model\ProcessQueueType\Rabbitmq\Publisher;

/**
 *
 */
class Rabbitmq implements ProcessQueueTypeInterface
{
    private Publisher $publisher;

    /**
     * @param Publisher $publisher
     */
    public function __construct(
        Publisher $publisher,
    )
    {
        $this->publisher = $publisher;
    }

    /**
     * @param array $job
     * @return void
     */
    public function execute(array $job)
    {
        $this->publisher->execute($job);
    }
}
