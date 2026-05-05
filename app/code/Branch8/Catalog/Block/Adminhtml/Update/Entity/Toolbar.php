<?php

namespace Branch8\Catalog\Block\Adminhtml\Update\Entity;

class Toolbar extends \Magento\Staging\Block\Adminhtml\Update\Entity\Toolbar
{
    const ENABLE_SCHEDULE_ADMIN = 'branch8_catalog/schedule_setting/enable_backend';
    protected function _prepareLayout(){
        parent::_prepareLayout();
        if(!$this->_scopeConfig->getValue(self::ENABLE_SCHEDULE_ADMIN)) {
            $this->buttonList->remove('staging_update_new');

            $params = $this->hasData('requestFieldName')
                ? [$this->getData('requestFieldName') => $this->getRequest()->getParam($this->getData('requestFieldName'))]
                : [];
            $modalPath = $this->hasData('modalPath') ? $this->getData('modalPath') : '';
            $loaderPath = $this->hasData('loaderPath') ? $this->getData('loaderPath') : '';
            $this->buttonList->add(
                'staging_update_new2',
                [
                    'label' => __('Schedule New Update'),
                    'class' => 'action action-secondary disabled',
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
            $this->toolbar->pushButtons($this, $this->buttonList);
        }
    }
}