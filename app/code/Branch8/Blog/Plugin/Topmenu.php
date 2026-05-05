<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Branch8\Blog\Block\Category\Menu;
use Branch8\Blog\Helper\Data;

/**
 * Class Topmenu
 * @package Branch8\Blog\Plugin
 */
class Topmenu
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * Topmenu constructor.
     *
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Theme\Block\Html\Topmenu $subject
     * @param $html
     *
     * @return string
     * @throws LocalizedException
     */
    public function afterGetHtml(
        \Magento\Theme\Block\Html\Topmenu $subject,
        $html
    ) {
        if ($this->helper->isEnabled() && $this->helper->getBlogConfig('display/toplinks')) {
            $blogHtml = $subject->getLayout()->createBlock(Menu::class)
                ->setTemplate('Branch8_Blog::category/topmenu.phtml')->toHtml();

            return $html . $blogHtml;
        }

        return $html;
    }
}
