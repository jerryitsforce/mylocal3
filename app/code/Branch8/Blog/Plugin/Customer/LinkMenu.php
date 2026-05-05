<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Plugin\Customer;

use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\View\Element\Html\Link;
use Magento\Framework\View\Element\Html\Links;
use Branch8\Blog\Helper\Data;

/**
 * Class LinkMenu
 * @package Branch8\Blog\Plugin\Customer
 */
class LinkMenu
{
    /**
     * @var ModuleManager
     */
    protected $_moduleManager;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * Topmenu constructor.
     *
     * @param Data $helper
     * @param ModuleManager $moduleManager
     */
    public function __construct(
        ModuleManager $moduleManager,
        Data $helper
    ) {
        $this->_moduleManager = $moduleManager;
        $this->_helper        = $helper;
    }

    /**
     * @param Links $subject
     * @param Link[] $links
     *
     * @return mixed
     */
    public function afterGetLinks(
        Links $subject,
        $links
    ) {
        if ($this->_moduleManager->isEnabled('branch8_blogPro') && $this->_helper->getPostViewPageConfig('enable_to_save')) {
            return $links;
        } else {
            $links = $this->unsetLinks($links);
        }

        return $links;
    }

    /**
     * @param $links
     *
     * @return mixed
     */
    protected function unsetLinks($links)
    {
        if ($links && !$this->_helper->getConfigGeneral('customer_approve')) {
            foreach ($links as $key => $link) {
                if ($link->getPath() === 'blog/author/signup') {
                    $this->_helper->setCustomerContextId();
                    $author = $this->_helper->getCurrentAuthor();
                    if ($author === null || !$author->getId()) {
                        unset($links[$key]);
                    }
                }
            }
        }

        return $links;
    }

}
