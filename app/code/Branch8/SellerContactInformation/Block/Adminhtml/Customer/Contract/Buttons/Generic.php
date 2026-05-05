<?php

namespace Branch8\SellerContactInformation\Block\Adminhtml\Customer\Contract\Buttons;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Yournamespace\Brand\Model\Api\Data\BrandInterface;

class Generic{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @param Context $context
     */
    public function __construct(Context $context){
        $this->context = $context;
    }

    public function getEntityId(){
        try{
            return $this->context->getRequest()->getParam('id');
        }catch (NoSuchEntityException $e){
            return null;
        }
    }

    public function getUrl($route = '', $params = []){
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}