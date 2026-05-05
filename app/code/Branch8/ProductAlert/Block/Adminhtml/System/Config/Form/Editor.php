<?php

namespace Branch8\ProductAlert\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Magento\Cms\Model\Wysiwyg\Config;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Editor extends Field
{
    /**
     * If field has attribute witch started with this prefix, value will be set to wysiwyg config
     * Wysiwyg key is symbols after prefix
     */
    public const EDITOR_CONFIG_ATTRIBUTE_PREFIX = 'pr_editor_';

    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config
     */
    protected $_wysiwygConfig;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Cms\Model\Wysiwyg\Config       $wysiwygConfig
     * @param array                                   $data
     */
    public function __construct(
        Context $context,
        Config $wysiwygConfig,
        array $data = []
    ) {
        $this->_wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $data);
    }

    /**
     * Get element html
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $fieldConfig = $element->getData('field_config');
        $config = $this->_wysiwygConfig->getConfig();

        foreach ($fieldConfig as $key => $value) {
            if (strpos($key, self::EDITOR_CONFIG_ATTRIBUTE_PREFIX) === 0) {
                $wysiwygKey = str_replace(self::EDITOR_CONFIG_ATTRIBUTE_PREFIX, '', $key);
                $config->setData($wysiwygKey, $value);
            }
        }

        $element->setConfig($config);
        return $element->getElementHtml() . $this->_getJs();
    }

    /**
     * Receive js html
     *
     * @return string
     */
    protected function _getJs()
    {
        return "<script>require(['Magento_Variable/variables']);</script>";
    }
}
