<?php
namespace Branch8\CatalogRule\Block\Widget\Grid\Column\Renderer;

class ReviewStatus extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    public function render(\Magento\Framework\DataObject $row)
    {
        if(!$row->getData('approval_id')){
            return '';
        }
        $postData = json_decode((string)$row->getData('post_data'), true);
        if(empty($postData)){
            return '';
        }

        return __('Pending');
        
        // $ruleId  = $postData['rule_id'];
        // if((int)$ruleId){
        //     return __('Under review');
        // }else{
        //     return __('Pending');
        // }
    }
}
