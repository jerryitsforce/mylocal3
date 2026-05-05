<?php

declare(strict_types=1);

namespace Branch8\HelpDesk\Block\Adminhtml\Ticket\Edit\Tab\Message;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Backend\Block\Template;

/**
 * Class MessageForm
 */
class MessageForm extends Template implements RendererInterface
{
    /**
     * @var AbstractElement|null
     */
    protected $_element = null;

    /**
     * @var string
     */
    protected $_template = 'Branch8_HelpDesk::ticket/edit/messages.phtml';

    /**
     * Render given element (return html of element)
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $this->setElement($element);
        return $this->toHtml();
    }

    /**
     * Setter
     *
     * @param AbstractElement $element
     * @return $this
     */
    public function setElement(AbstractElement $element)
    {
        $this->_element = $element;
        return $this;
    }

    /**
     * Getter
     *
     * @return AbstractElement
     */
    public function getElement()
    {
        return $this->_element;
    }

}
