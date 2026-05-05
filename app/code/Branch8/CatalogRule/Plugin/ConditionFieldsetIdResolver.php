<?php
namespace Branch8\CatalogRule\Plugin;

class ConditionFieldsetIdResolver
{
    protected $_request;

    public function __construct(
        \Magento\Framework\App\Request\Http $request
    )
    {
        $this->_request = $request;
    }

    public function aroundGetConditionsFieldSetId(
        \Magento\CatalogRule\Model\Rule $subject,
        \Closure $proceed,
        $formName = ''
    ) {
        $fieldsetId = $proceed($formName);
        $urlKey = $this->_request->getParam('key');
        return $fieldsetId.'_'.$urlKey;
    }
}
