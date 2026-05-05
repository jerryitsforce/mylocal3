<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Block\Html;

use Magento\Framework\View\Element\Html\Link;
use Magento\Framework\View\Element\Template\Context;
use Branch8\Blog\Helper\Data;

/**
 * Class Footer
 * @package Branch8\Blog\Block\Html
 */
class Footer extends Link
{
    /**
     * @var Data
     */
    public $helper;

    /**
     * @var string
     */
    protected $_template = 'Branch8_Blog::html\footer.phtml';

    /**
     * Footer constructor.
     *
     * @param Context $context
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;

        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getHref()
    {
        return $this->helper->getBlogUrl('');
    }

    /**
     * @return \Magento\Framework\Phrase|mixed|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getLabel()
    {
        return $this->helper->getBlogConfig('display/name', $this->helper->getCurrentStoreId()) ? : __('Blog');
    }

    /**
     * @return Data
     */
    public function getHelperData()
    {
        return $this->helper;
    }
}
