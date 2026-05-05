<?php
namespace Branch8\CatalogRule\Plugin\Block\Edit;

class DeleteButton extends \Magento\CatalogRule\Block\Adminhtml\Edit\SaveAndApplyButton
{
    protected $request;

    protected $_authorization;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\AuthorizationInterface $authorization
    ) {
        parent::__construct($context, $registry);
        $this->request = $request;
        $this->_authorization = $authorization;
    }

    public function afterGetButtonData($subject, $result){
        $fullAction = $this->request->getFullActionName();
        if(!in_array($fullAction, ['promocatalog_approval_detail', 'promocatalog_history_compareVersion', 'promocatalog_history_detail'])){
            if($this->_authorization->isAllowed(\Branch8\CatalogRule\Rewrite\Controller\Adminhtml\Promo\Catalog\Delete::ADMIN_RESOURCE)){
                return $result;
            }else{
                return [];
            }
            
        }
        return [];
    }
}
