<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Plugin\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\Manager;
use Mageplaza\Core\Block\Adminhtml\System\Config\Docs;

/**
 * Class Banner
 * @package Branch8\Blog\Plugin\System\Config
 */
class Banner
{
    /**
     * @var Manager
     */
    protected $_moduleManager;

    /**
     * Banner constructor.
     *
     * @param Manager $moduleManager
     */
    public function __construct(
        Manager $moduleManager
    ) {
        $this->_moduleManager = $moduleManager;
    }

    /**
     * @param Docs $subject
     * @param $result
     * @param AbstractElement $element
     *
     * @return mixed
     */
    public function afterRender(Docs $subject, $result, AbstractElement $element)
    {
        if ($this->isHideBanner($element)) {
            return $result;
        }
        $bannerImg = $subject->getViewFileUrl('Branch8_Blog::media/banner/banner.png');
        $html      = <<<HTML
        <script>
            require([ 'jquery'], function ($) {
                var session = $(".accordion" );
                $("<a target='_blank' href='https://www.mageplaza.com/magento-2-better-blog/?utm_source=dashboard&utm_medium=admin&utm_campaign=blogpro'>" +
                 "<img src='{$bannerImg}'></a>").insertBefore(session);
            })
        </script>
        HTML;

        $result = $html . $result;

        return $result;
    }

    /**
     * @param $element
     * @return bool
     */
    protected function isHideBanner($element)
    {
        if ($element->getOriginalData()['module_name'] !== 'Branch8_Blog') {
            return true;
        }

        if ($this->_moduleManager->isOutputEnabled('branch8_blogPro')) {
            return true;
        }

        return false;
    }
}
