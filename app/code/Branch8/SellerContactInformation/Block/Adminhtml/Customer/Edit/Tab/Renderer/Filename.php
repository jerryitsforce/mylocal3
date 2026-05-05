<?php

namespace Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit\Tab\Renderer;

class Filename extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row){
        $filename = $row->getFileName();
        if($filename){
            $fileHtml = '<a href="'.$this->_urlBuilder->getUrl('marketplace/contractFile/download', ['seller_id' => $row['seller_id'], 'entity_id' => $row['entity_id']]).'">'.$filename.'</a>';
            return $fileHtml;
        }

        return $filename;
    }
}