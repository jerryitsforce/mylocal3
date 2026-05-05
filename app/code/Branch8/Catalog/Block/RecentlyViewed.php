<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\Catalog\Block;

use Magento\Catalog\Block\Widget\RecentlyViewed as CoreRecentlyViewed;
use Magento\Framework\Exception\ValidatorException;

/**
 * Dynamically creates recently viewed widget ui component, using information
 * from widget instance and Catalog/widget.xml
 */
class RecentlyViewed extends CoreRecentlyViewed implements \Magento\Widget\Block\BlockInterface
{
    /**
     * Render block HTML
     *
     * @return string
     * @throws ValidatorException
     */
    protected function _toHtml()
    {
        if (!$this->getTemplate()) {
            return '';
        }
        $html = $this->getLayout()->createBlock('Magento\Cms\Block\Block')->setBlockId('mini-cart-browsing-history')->toHtml();
        return '<li class="top-recently-viewed">'.$html.'</li>';
    }
}
