<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Block\Adminhtml\Topic\Edit\Tab;

use Branch8\Blog\Helper\Image;
use Branch8\Blog\Model\Config\Source\BackgroundPosition;
use Branch8\Blog\Model\Config\Source\BackgroundSize;
use Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Cms\Model\Wysiwyg\Config;
use Magento\Config\Model\Config\Source\Design\Robots;
use Magento\Config\Model\Config\Source\Enabledisable;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\System\Store;

/**
 * Class Topic
 * @package Branch8\Blog\Block\Adminhtml\Topic\Edit\Tab
 */
class Topic extends Generic implements TabInterface
{
    /**
     * Wysiwyg config
     *
     * @var Config
     */
    protected $wysiwygConfig;

    /**
     * Country options
     *
     * @var Yesno
     */
    protected $booleanOptions;

    /**
     * @var Enabledisable
     */
    protected $enableDisable;

    /**
     * @var Robots
     */
    protected $metaRobotsOptions;

    /**
     * @var Store
     */
    protected $systemStore;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var BackgroundSize
     */
    protected $_backgroundSize;

    /**
     * @var BackgroundPosition
     */
    protected $_backgroundPosition;

    /**
     * Topic constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Config $wysiwygConfig
     * @param Yesno $booleanOptions
     * @param Enabledisable $enableDisable
     * @param Robots $metaRobotsOptions
     * @param Store $systemStore
     * @param Image $imageHelper
     * @param BackgroundSize $backgroundSize
     * @param BackgroundPosition $backgroundPosition
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Config $wysiwygConfig,
        Yesno $booleanOptions,
        Enabledisable $enableDisable,
        Robots $metaRobotsOptions,
        Store $systemStore,
        Image $imageHelper,
        BackgroundSize $backgroundSize,
        BackgroundPosition $backgroundPosition,
        array $data = []
    ) {
        $this->wysiwygConfig     = $wysiwygConfig;
        $this->booleanOptions    = $booleanOptions;
        $this->enableDisable     = $enableDisable;
        $this->metaRobotsOptions = $metaRobotsOptions;
        $this->systemStore       = $systemStore;
        $this->imageHelper       = $imageHelper;
        $this->_backgroundSize   = $backgroundSize;
        $this->_backgroundPosition = $backgroundPosition;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareForm()
    {
        /** @var \Branch8\Blog\Model\Topic $topic */
        $topic = $this->_coreRegistry->registry('branch8_blog_topic');

        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('topic_');
        $form->setFieldNameSuffix('topic');

        $fieldset = $form->addFieldset('base_fieldset', [
            'legend' => __('Topic Information'),
            'class'  => 'fieldset-wide'
        ]);
        if ($topic->getId()) {
            $fieldset->addField('topic_id', 'hidden', ['name' => 'topic_id']);
        }

        $fieldset->addField('name', 'text', [
            'name'     => 'name',
            'label'    => __('Name'),
            'title'    => __('Name'),
            'required' => true,
        ]);
        $fieldset->addField('url_key', 'text', [
            'name'  => 'url_key',
            'label' => __('URL Key'),
            'title' => __('URL Key')
        ]);
        $fieldset->addField('enabled', 'select', [
            'name'   => 'enabled',
            'label'  => __('Status'),
            'title'  => __('Status'),
            'values' => $this->enableDisable->toOptionArray(),
        ]);
        if (!$topic->hasData('enabled')) {
            $topic->setEnabled(1);
        }

        $fieldset->addField('image', \Branch8\Blog\Block\Adminhtml\Renderer\Image::class, [
            'name'  => 'image',
            'label' => __('Image'),
            'title' => __('Image'),
            'path'  => $this->imageHelper->getBaseMediaPath(Image::TEMPLATE_MEDIA_TYPE_TOPIC),
            'note'  => __('The appropriate size is 265px * 250px.')
        ]);
        $fieldset->addField('background_size', 'select', [
            'name'   => 'background_size',
            'label'  => __('Background Size'),
            'title'  => __('Background Size'),
            'values' => $this->_backgroundSize->toOptionArray()
        ]);
        $fieldset->addField('background_position', 'select', [
            'name'   => 'background_position',
            'label'  => __('Background Position'),
            'title'  => __('Background Position'),
            'values' => $this->_backgroundPosition->toOptionArray()
        ]);

        $fieldset->addField('description', 'editor', [
            'name'   => 'description',
            'label'  => __('Description'),
            'title'  => __('Description'),
            'config' => $this->wysiwygConfig->getConfig(['add_variables' => false, 'add_widgets' => false])
        ]);

        if (!$this->_storeManager->isSingleStoreMode()) {
            /** @var RendererInterface $rendererBlock */
            $rendererBlock = $this->getLayout()->createBlock(
                Element::class
            );
            $fieldset->addField('store_ids', 'multiselect', [
                'name'   => 'store_ids',
                'label'  => __('Store Views'),
                'title'  => __('Store Views'),
                'values' => $this->systemStore->getStoreValuesForForm(false, true)
            ])->setRenderer($rendererBlock);
            if (!$topic->hasData('store_ids')) {
                $topic->setStoreIds(0);
            }
        } else {
            $fieldset->addField('store_ids', 'hidden', [
                'name'  => 'store_ids',
                'value' => $this->_storeManager->getStore()->getId()
            ]);
        }

        $form->addValues($topic->getData());
        $this->setForm($form);

        $this->_eventManager->dispatch('adminhtml_blog_edit_form_prepare_form', ['block' => $this]);

        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Topic');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * Can show tab in tabs
     *
     * @return boolean
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }
}
