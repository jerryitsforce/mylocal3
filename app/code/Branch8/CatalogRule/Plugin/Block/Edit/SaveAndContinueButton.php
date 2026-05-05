<?php
namespace Branch8\CatalogRule\Plugin\Block\Edit;

class SaveAndContinueButton extends \Magento\CatalogRule\Block\Adminhtml\Edit\SaveAndApplyButton
{
    protected $request;

    protected $crHelperData;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\RequestInterface $request,
        \Branch8\CatalogRule\Helper\Data $crHelperData
    ) {
        parent::__construct($context, $registry);
        $this->request = $request;
        $this->crHelperData = $crHelperData;
    }

    public function afterGetButtonData($subject, $result){
        $fullAction = $this->request->getFullActionName();
        if(in_array($fullAction, ['promocatalog_approval_detail', 'promocatalog_history_compareVersion', 'promocatalog_history_detail'])){
            return [];
        }
        if($this->crHelperData->isApprovalEnable()){
            return [];
        }
        return $result;
    }
}
