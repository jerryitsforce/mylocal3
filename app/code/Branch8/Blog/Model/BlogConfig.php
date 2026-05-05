<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Model;

use Magento\Framework\DataObject;
use Branch8\Blog\Api\Data\BlogConfigInterface;

/**
 * Class BlogConfig
 * @package Branch8\Blog\Model
 */
class BlogConfig extends DataObject implements BlogConfigInterface
{

    /**
     * {@inheritdoc}
     */
    public function getGeneral()
    {
        return $this->getData(self::GENERAL);
    }

    /**
     * {@inheritdoc}
     */
    public function setGeneral($value)
    {
        $this->setData(self::GENERAL, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSidebar()
    {
        return $this->getData(self::SIDEBAR);
    }

    /**
     * {@inheritdoc}
     */
    public function setSidebar($value)
    {
        $this->setData(self::SIDEBAR, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSeo()
    {
        return $this->getData(self::SEO);
    }

    /**
     * {@inheritdoc}
     */
    public function setSeo($value)
    {
        $this->setData(self::SEO, $value);

        return $this;
    }
}
