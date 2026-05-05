<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       03/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Participant extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(
            'branch8_marketplace_product_discussion_participant',
            'participant_id'
        );
    }
}
