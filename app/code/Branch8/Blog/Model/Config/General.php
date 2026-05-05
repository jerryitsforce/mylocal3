<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Model\Config;

use Magento\Framework\DataObject;
use Branch8\Blog\Api\Data\Config\GeneralInterface;

/**
 * Class General
 * @package Branch8\Blog\Model\Config
 */
class General extends DataObject implements GeneralInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBlogName()
    {
        return $this->getData(self::BLOG_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setBlogName($value)
    {
        $this->setData(self::BLOG_NAME, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIsLinkInMenu()
    {
        return $this->getData(self::IS_LINK_IN_MENU);
    }

    /**
     * {@inheritdoc}
     */
    public function setIsLinkInMenu($value)
    {
        $this->setData(self::IS_LINK_IN_MENU, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIsDisplayAuthor()
    {
        return $this->getData(self::IS_DISPLAY_AUTHOR);
    }

    /**
     * {@inheritdoc}
     */
    public function setIsDisplayAuthor($value)
    {
        $this->setData(self::IS_DISPLAY_AUTHOR, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlogMode()
    {
        return $this->getData(self::BLOG_MODE);
    }

    /**
     * {@inheritdoc}
     */
    public function setBlogMode($value)
    {
        $this->setData(self::BLOG_MODE, $value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlogColor()
    {
        return $this->getData(self::BLOG_COLOR);
    }

    /**
     * {@inheritdoc}
     */
    public function setBlogColor($value)
    {
        $this->setData(self::BLOG_COLOR, $value);

        return $this;
    }
}
