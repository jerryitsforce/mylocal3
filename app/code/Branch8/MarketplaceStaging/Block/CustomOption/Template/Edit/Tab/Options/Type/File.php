<?php
/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Branch8-Commerce-License.txt
 *
 * @category   BSS
 * @package    Branch8_MarketplaceStaging
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2020 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Branch8-Commerce-License.txt
 */
namespace Branch8\MarketplaceStaging\Block\CustomOption\Template\Edit\Tab\Options\Type;

class File extends \Magento\Catalog\Block\Adminhtml\Product\Edit\Tab\Options\Type\File
{
    /**
     * @var string
     */
    protected $_template = 'Branch8_MarketplaceStaging::catalog/product/edit/options/type/file.phtml';

    /**
     * Create button and return its html
     *
     * @param string $label
     * @param string $onclick
     * @param string $class
     * @param string $buttonId
     * @param array $dataAttr
     * @return string
     */
    public function getButtonHtml($label, $onclick, $class = '', $buttonId = null, $dataAttr = [])
    {
        return $this->getLayout()->createBlock(
            \Branch8\MarketplaceStaging\Block\Widget\Button::class
        )->setData(
            [
                'label' => $label,
                'onclick' => $onclick,
                'class' => $class,
                'type' => 'button',
                'id' => $buttonId
            ]
        )->setDataAttribute(
            $dataAttr
        )->toHtml();
    }
}
