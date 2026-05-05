<?php
declare(strict_types=1);

namespace Branch8\RmaAdminUi\Block\Adminhtml\Form\Renderer;
class Tags extends \Magento\Backend\Block\Template implements
    \Magento\Framework\Data\Form\Element\Renderer\RendererInterface

{
    /**
     * Reward rate template
     *
     * @var string
     */
    protected $_template = 'Branch8_RmaAdminUi::form/renderer/tags.phtml';

    /**
     * Return HTML
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setElement($element);
        return $this->toHtml();
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->getReason()->getTags();
    }
}
