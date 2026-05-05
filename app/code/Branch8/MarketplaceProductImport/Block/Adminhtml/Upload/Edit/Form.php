<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_MpMassUpload
 * @author    Webkul Software Private Limited
 * @copyright Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Branch8\MarketplaceProductImport\Block\Adminhtml\Upload\Edit;

class Form extends \Webkul\MpMassUpload\Block\Adminhtml\Upload\Edit\Form
{
    /**
     * Prepare form.
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        parent::_prepareForm();
        
        $form = $this->getForm();
        if ($form) {
            $field = $form->getElement('massupload_csv');
            if ($field) {
                $field->setLabel(__('Upload Csv/XML/XLS/XLSX'));
                $field->setTitle(__('Upload Csv/XML/XLS/XLSX'));
            }
        }
        
        return $this;
    }
}
