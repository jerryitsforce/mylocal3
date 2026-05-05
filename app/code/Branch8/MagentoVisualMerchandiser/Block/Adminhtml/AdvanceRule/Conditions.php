<?php
declare(strict_types=1);
namespace Branch8\MagentoVisualMerchandiser\Block\Adminhtml\AdvanceRule;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Conditions implements \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{
    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        /**
         * @var $rule \Branch8\MagentoVisualMerchandiser\Model\AdvanceRule
         */
        if ($element->getRule() && $element->getRule()->getConditions()) {
            $rule = $element->getRule();
            $conditions = $rule->getConditions();
            return $conditions->asHtmlRecursive();
        }
        return '';
    }
}
