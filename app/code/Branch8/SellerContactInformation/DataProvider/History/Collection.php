<?php
namespace Branch8\SellerContactInformation\DataProvider\History;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{
    protected function _initSelect()
    {
        parent::_initSelect();
        $obj = \Magento\Framework\App\ObjectManager::getInstance();
        $request = $obj->get(\Magento\Framework\App\RequestInterface::class);
        $sellerId = $request->getParam('seller_id');
        $this->getSelect()
            ->where('seller_id = '.$sellerId)->order('entity_id desc');
        return $this;
    }
}
