<?php
namespace Branch8\CatalogRule\Plugin\Block\Edit;

class SaveAndApplyButton extends \Magento\CatalogRule\Block\Adminhtml\Edit\SaveAndApplyButton
{
    protected $request;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\RequestInterface $request
    ) {
        parent::__construct($context, $registry);
        $this->request = $request;
    }

    public function afterGetButtonData($subject, $result){
        $fullAction = $this->request->getFullActionName();
        if(!in_array($fullAction, ['promocatalog_approval_detail', 'promocatalog_history_compareVersion', 'promocatalog_history_detail'])){
            return $result;
        }
        return [];
    }
}
