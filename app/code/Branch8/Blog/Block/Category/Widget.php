<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Block\Category;

use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Phrase;
use Branch8\Blog\Block\Adminhtml\Category\Tree;
use Branch8\Blog\Block\Frontend;
use Branch8\Blog\Helper\Data;

/**
 * Class Widget
 * @package Branch8\Blog\Block\Category
 */
class Widget extends Frontend
{
    /**
     * @return mixed|null
     */
    public function getTree()
    {
        try {
            $tree = ObjectManager::getInstance()->create(Tree::class);
            $tree = $tree->getTree(null, $this->store->getStore()->getId());

            return $tree;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param array $tree
     *
     * @return Phrase|string
     */
    public function getCategoryTreeHtml($tree)
    {
        if (!$tree) {
            return __('No Categories.');
        }

        $html = '';
        foreach ($tree as $value) {
            if (!$value) {
                continue;
            }
            if ($value['enabled']) {
                $level = count(explode('/', ($value['path'])));
                $hasChild = isset($value['children']) && $level < 4;
                $html .= '<ul class="block-content menu-categories category-level'
                    . $level . '" style="margin-bottom:0px;margin-top:8px;">';
                $html .= '<li class="category-item">';
                $html .= $hasChild ? '<i class="fa fa-plus-square-o blog-expand-tree-' . $level . '"></i>' : '';
                $html .= '<a class="list-categories" href="' . $this->getCategoryUrl($value['url']) . '">';
                $html .= '<i class="fa fa-folder-open-o">&nbsp;&nbsp;</i>';
                $html .= ucfirst($value['text']) . '</a>';
                $html .= $hasChild ? $this->getCategoryTreeHtml($value['children']) : '';
                $html .= '</li>';
                $html .= '</ul>';
            }
        }

        return $html;
    }

    /**
     * @param string $category
     *
     * @return string
     */
    public function getCategoryUrl($category)
    {
        return $this->helperData->getBlogUrl($category, Data::TYPE_CATEGORY);
    }
}
