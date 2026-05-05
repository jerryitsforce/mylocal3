<?php
namespace Branch8\Cms\Observer;

class RestrictLoadBanner implements \Magento\Framework\Event\ObserverInterface
{
    protected $mobileDetect;

    public function __construct(
        \Branch8\HotaiCore\Model\Detection\MobileDetect $mobileDetect
    )
    {
        $this->mobileDetect = $mobileDetect;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $select = $observer->getData('select');
        $isHotaiApp = $this->mobileDetect->isHotaiApp();
        if($isHotaiApp){
            $select->where('visibility in('.\Branch8\Cms\Model\Config\Source\CmsAgent::TYPE_APP.', '.\Branch8\Cms\Model\Config\Source\CmsAgent::TYPE_UNRETRICTED.')');
        }else{
            $select->where('visibility in('.\Branch8\Cms\Model\Config\Source\CmsAgent::TYPE_WEB.', '.\Branch8\Cms\Model\Config\Source\CmsAgent::TYPE_UNRETRICTED.')');
        }
    }
}