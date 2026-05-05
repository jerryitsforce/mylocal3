<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\MarketplaceStaging\Block\Update\Entity;

use Magento\Framework\View\Element\Template;
use Branch8\MarketplaceStaging\Block\Widget\Button\ButtonList;
use Branch8\MarketplaceStaging\Block\Widget\Button\ToolbarInterface;
use Branch8\MarketplaceStaging\Block\Widget\Context;
use Branch8\MarketplaceStaging\Block\Widget\ContainerInterface;
use Magento\Framework\View\Element\AbstractBlock;

/**
 * @api
 * @since 100.1.0
 */
class Toolbar extends Template implements ContainerInterface
{
    const ENABLE_SCHEDULE_SELLER = 'branch8_catalog/schedule_setting/enable_seller';
    /**
     * @var ButtonList
     * @since 100.1.0
     */
    protected $buttonList;

    /**
     * @var ToolbarInterface
     * @since 100.1.0
     */
    protected $toolbar;

    /**
     * Container constructor.
     *
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        $this->buttonList = $context->getButtonList();
        $this->toolbar = $context->getButtonToolbar();
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     * @since 100.1.0
     */
    public function updateButton($buttonId, $key, $data)
    {
        $this->buttonList->update($buttonId, $key, $data);
        return $this;
    }

    /**
     * Create add button and grid blocks
     *
     * @return AbstractBlock
     * @since 100.1.0
     */
    protected function _prepareLayout()
    {
        $params = $this->hasData('requestFieldName')
            ? [$this->getData('requestFieldName') => $this->getRequest()->getParam($this->getData('requestFieldName'))]
            : [];
        $modalPath = $this->hasData('modalPath') ? $this->getData('modalPath') : '';
        $loaderPath = $this->hasData('loaderPath') ? $this->getData('loaderPath') : '';

        $disableClass = '';
        $isDisableSchedule = $this->_scopeConfig->getValue(self::ENABLE_SCHEDULE_SELLER);
        if(!$isDisableSchedule){
            $disableClass = 'disabled';
        }
        
        $this->buttonList->add(
            'staging_update_new',
            [
                'label' => __('Schedule New Update'),
                'class' => 'action action-secondary '.$disableClass.' ',
                'region' => 'staging.schedule.title',
                'data_attribute' => [
                    'mage-init' => [
                        'Magento_Ui/js/form/button-adapter' => [
                            'actions' => [
                                [
                                    'targetName' => $modalPath,
                                    'actionName' => 'openModal',
                                ],
                                [
                                    'targetName' => $loaderPath,
                                    'actionName' => 'destroyInserted',
                                ],
                                [
                                    'targetName' => $loaderPath,
                                    'actionName' => 'render',
                                    'params' => [
                                        $params
                                    ],
                                ],
                            ],
                        ]
                    ],
                ]
            ]
        );
        $this->buttonList->add(
            'staging_update_new_prevent',
            [
                'label' => __('Schedule New Update'),
                'class' => 'action action-secondary staging_update_new_prevent '.$disableClass.' ',
                'region' => 'staging.schedule.title'
            ]
        );
        $this->toolbar->pushButtons($this, $this->buttonList);
        return parent::_prepareLayout();
    }

    /**
     * {@inheritdoc}
     * @since 100.1.0
     */
    public function addButton($buttonId, $data, $level = 0, $sortOrder = 0, $region = 'toolbar')
    {
        $this->buttonList->add($buttonId, $data, $level, $sortOrder, $region);
        return $this;
    }

    /**
     * {@inheritdoc}
     * @since 100.1.0
     */
    public function removeButton($buttonId)
    {
        $this->buttonList->remove($buttonId);
        return $this;
    }

    /**
     * {@inheritdoc}
     * @since 100.1.0
     */
    public function canRender(\Branch8\MarketplaceStaging\Block\Widget\Button\Item $item)
    {
        return !$item->isDeleted();
    }
}
