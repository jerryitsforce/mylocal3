<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       25/02/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Vote extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init(
            'branch8_marketplace_product_discussion_vote',
            'vote_id'
        );
    }
}

