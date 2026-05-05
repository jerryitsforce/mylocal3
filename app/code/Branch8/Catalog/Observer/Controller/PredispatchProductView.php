<?php

namespace Branch8\Catalog\Observer\Controller;

use Magento\Framework\App\Config\ScopeConfigInterface;

class PredispatchProductView implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\App\ActionFlag
     */
    protected $actionFlag;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\ActionFlag $actionFlag
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\ActionFlag $actionFlag
    )
    {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->actionFlag = $actionFlag;
    }

    public function execute($observer){
        $controllerAction = $observer->getData('controller_action');
        $request = $controllerAction->getRequest();
        $pdpPrefix = $this->scopeConfig->getValue(\Branch8\Catalog\Rewrite\Model\Product\Url::PDP_PREFIX);
        $currentURI = $request->getServer('REQUEST_URI');
        $currentURI = substr($currentURI, 1);
        if ($request->getParam('___version')) return;
        
        if($pdpPrefix && strpos($currentURI, $pdpPrefix) !== 0){
            $slashCheck = strpos($currentURI, '/');
            if(strpos($currentURI, 'catalog/product/view') !== false){
                return;
            }else{
                if($slashCheck !== false){
                    $exUri = explode('/', $currentURI);
                    $currentURI = end($exUri);
                }
                $newURL = $this->storeManager->getStore()->getBaseUrl().$pdpPrefix.$currentURI;
                
                $this->actionFlag->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);
                $controllerAction->getResponse()->setRedirect($newURL)->sendResponse();
            }
            
            return;
        }
    }
}
