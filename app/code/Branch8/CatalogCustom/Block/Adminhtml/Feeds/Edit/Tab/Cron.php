<?php

namespace Branch8\CatalogCustom\Block\Adminhtml\Feeds\Edit\Tab;

class Cron extends \Wyomind\DataFeedManager\Block\Adminhtml\Feeds\Edit\Tab\Cron
{
    /**
     * @return \Magento\Backend\Block\Widget\Form\Generic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('data_feed');

        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('');

        $form->setValues($model->getData());
        $this->setForm($form);

        $this->setTemplate('Branch8_CatalogCustom::edit/cron.phtml');

        return \Magento\Backend\Block\Widget\Form\Generic::_prepareForm();
    }
}
