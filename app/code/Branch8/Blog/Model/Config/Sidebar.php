<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Model\Config;

use Magento\Framework\DataObject;
use Branch8\Blog\Api\Data\Config\SidebarInterface;

/**
 * Class Sidebar
 * @package Branch8\Blog\Model\Config
 */
class Sidebar extends DataObject implements SidebarInterface
{
    /**
     * {@inheritdoc}
     */
    public function getNumberRecent()
    {
        return $this->getData(self::NUMBER_RECENT);
    }

    /**
     * {@inheritdoc}
     */
    public function setNumberRecent($value)
    {
        $this->setData(self::NUMBER_RECENT, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNumberMostView()
    {
        return $this->getData(self::NUMBER_MOST_VIEW);
    }

    /**
     * {@inheritdoc}
     */
    public function setNumberMostView($value)
    {
        $this->setData(self::NUMBER_MOST_VIEW, $value);

        return $this;
    }
}
