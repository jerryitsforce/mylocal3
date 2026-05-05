<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       20/03/2026
 */

namespace Branch8\BlackListKeyWords\Model;
use Branch8\BlackListKeyWords\Api\Data\KeywordInterface;
use Magento\Framework\Model\AbstractModel;

class Keyword extends AbstractModel implements KeywordInterface
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Branch8\BlackListKeyWords\Model\ResourceModel\Keyword::class);
    }
}
