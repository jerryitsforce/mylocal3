<?php
namespace Branch8\CatalogRule\Rewrite\Block\Adminhtml\Promo\Catalogrule\Edit\Tab\Banners;

class Grid extends \Magento\Banner\Block\Adminhtml\Promo\Catalogrule\Edit\Tab\Banners\Grid
{
    protected $catalogRuleApprovalFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory $bannerColFactory,
        \Magento\Banner\Model\Config $bannerConfig,
        \Magento\Framework\Registry $registry,
        \Magento\Banner\Model\BannerFactory $bannerFactory,
        \Branch8\CatalogRule\Model\CatalogRuleApprovalFactory $catalogRuleApprovalFactory,
        array $data = []
    ){
        $this->catalogRuleApprovalFactory = $catalogRuleApprovalFactory;
        parent::__construct($context, $backendHelper, $bannerColFactory, $bannerConfig, $registry, $bannerFactory, $data);
    }

    public function getSelectedCatalogruleBanners(){
        $approvalId = $this->getRequest()->getParam('approval_id');

        $fullActionName = $this->getRequest()->getFullActionName();
        if($fullActionName == 'promocatalog_approval_detailApproval' && $approvalId){
            $apprModel = $this->catalogRuleApprovalFactory->create()
                ->load($approvalId);
            $postData = $apprModel->getPostData();
            $postDataArr = json_decode($postData, true);
            if(!isset($postDataArr['related_banners'])){
                return [];
            }
            $relatedBanners = $postDataArr['related_banners'];
            return $relatedBanners;
        }else if($fullActionName == 'promocatalog_history_compareVersion' || $fullActionName == 'promocatalog_history_compareVersion2'){
            $approvedData = $this->_registry->registry('versionData');
            $approvedDataArr = json_decode($approvedData, true);
            if(!isset($approvedDataArr['related_banners'])){
                return [];
            }
            $relatedBanners = $approvedDataArr['related_banners'];
            return $relatedBanners;
        }else if($fullActionName == 'promocatalog_history_detail' || $fullActionName == 'promocatalog_history_detail2'){
            $approvedData = $this->_registry->registry('versionData');
            $approvedDataArr = json_decode($approvedData, true);
            if(!isset($approvedDataArr['related_banners'])){
                return [];
            }
            $relatedBanners = $approvedDataArr['related_banners'];
            return $relatedBanners;
        }

        return parent::getSelectedCatalogruleBanners();
    }
}
