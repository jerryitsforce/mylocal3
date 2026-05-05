<?php

namespace Branch8\Customer\Block\Adminhtml\Customer\Edit\Tab\Renderer;

class Detail extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer{
    /**
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row){
        $detailData = $row->getDetails();
        $details = json_decode($detailData, true);
        $detailHtml = '';
        if(!$details){
            return $detailHtml;
        }
        foreach($details as $key => $detail){
            if(is_string($detail)){
                $detailHtml .= ucfirst($key) . ':' . $detail . '<br />';
            }else{
                $detailHtml .= ucfirst($key) . ':' . json_encode($detail) . '<br />';
            }
        }
        return $detailHtml;
    }
}