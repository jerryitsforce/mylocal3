<?php
namespace Branch8\AppSettings\Block\Adminhtml\Form\Field;


use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

abstract class AbstractVersion extends AbstractFieldArray
{
    protected $_template = 'Branch8_AppSettings::system/config/form/field/version.phtml';

    /**
     * Rows cache
     *
     * @var array|null
     */
    private $_arrayRowsCache;

    /**
     * Get the grid and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->setElement($element);
        $html = $this->_toHtml();
        $this->_arrayRowsCache = null;
        // doh, the object is used as singleton!
        return $html;
    }

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
//        $this->addColumn('major', ['label' => __('Major'), 'class' => 'required-entry validate-greater-than-zero validate-digits']);
//        $this->addColumn('minor', ['label' => __('Minor'), 'class' => 'required-entry validate-greater-than-zero validate-digits']);
//        if($isAndroid) {
//            $this->addColumn('patch', ['label' => __('Patch'), 'class' => 'required-entry validate-greater-than-zero validate-digits']);
//        }
//        $this->addColumn('build', ['label' => __('Build Number'), 'class' => 'required-entry validate-greater-than-zero validate-digits']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Obtain existing data from form element
     *
     * Each row will be instance of \Magento\Framework\DataObject
     *
     * @return array
     */
    public function getArrayRows()
    {
        if (null !== $this->_arrayRowsCache) {
            return $this->_arrayRowsCache;
        }
        $result = [];
        /** @var \Magento\Framework\Data\Form\Element\AbstractElement */
        $element = $this->getElement();

        if(empty($element->getValue())) {
            $element->setValue([
                uniqid() => [
                    'major' => '1',
                    'minor' => '0',
                    'patch' => '0',
                    'build' => '0'
                ]
            ]);
        }

        if ($element->getValue() && is_array($element->getValue())) {
            foreach ($element->getValue() as $rowId => $row) {
                $rowColumnValues = [];
                foreach ($row as $key => $value) {
                    $row[$key] = $value;
                    $rowColumnValues[$this->_getCellInputElementId($rowId, $key)] = $row[$key];
                }
                $row['_id'] = $rowId;
                $row['column_values'] = $rowColumnValues;
                $result[$rowId] = new \Magento\Framework\DataObject($row);
                $this->_prepareArrayRow($result[$rowId]);
            }
        }
        $this->_arrayRowsCache = $result;
        return $this->_arrayRowsCache;
    }

}
