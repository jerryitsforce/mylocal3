<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

/**
 * Class Category
 * @package Branch8\Blog\Block\Adminhtml
 */
class Category extends Container
{
    /**
     * constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_category';
        $this->_blockGroup = 'Branch8_Blog';
        $this->_headerText = __('Categories');
        $this->_addButtonLabel = __('Create New Blog Category');

        parent::_construct();
    }
}