<?php

namespace Branch8\Sales\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Area;
use Magento\Backend\Model\Auth\Session;
use Webkul\SellerSubAccount\Helper\Data as HelperData;
use Magento\Customer\Model\Session as CustomerSession;
use PDO;

class StatusChangeLog extends AbstractHelper
{
    protected $appState;

    protected $authSession;

    protected $b8CustomerHelper;

    protected $subAccountHelper;

    protected $customerSession;

    protected $request;

    protected $remoteAddress;

    protected $registry;

    public function __construct(
        Context $context,
        \Magento\Framework\App\State $appState,
        Session $authSession,
        \Branch8\Customer\Helper\Data $b8CustomerHelper,
        HelperData $subAccountHelper,
        CustomerSession $customerSession,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Framework\Registry $registry
   )
    {
        parent::__construct($context);
        $this->appState = $appState;
        $this->authSession = $authSession;
        $this->subAccountHelper = $subAccountHelper;
        $this->customerSession = $customerSession;
        $this->request = $request;
        $this->remoteAddress = $remoteAddress;
        $this->registry = $registry;
        $this->b8CustomerHelper = $b8CustomerHelper;
    }

    public function detectResource(){
        $resource = [];
        try{
            $areaCode = $this->appState->getAreaCode();
        }catch(\Exception $e){
            $areaCode = '';
            $resource['area'] = '';
        }
        try{
            if($areaCode == Area::AREA_ADMINHTML){
                $resource['area'] = Area::AREA_ADMINHTML;
                $resource['user'] = $this->authSession->getUser()->getUserName();
            }else if($areaCode == Area::AREA_FRONTEND){
                $resource['area'] = Area::AREA_FRONTEND;
                /**
                 * Is seller
                 */
                if($this->b8CustomerHelper->isSeller()){
                    $resource['role'] = 'Seller';
                }else if($this->subAccountHelper->isSubAccount()){
                    $resource['role'] = 'Sub Account';
                }else{
                    $resource['role'] = 'Buyer';
                }
                $customer = $this->customerSession->getCustomer();
                if($customer){
                    $customer->getName();
                }
            }else if($areaCode == Area::AREA_WEBAPI_REST){
                $resource['area'] = Area::AREA_WEBAPI_REST;
                $resource['ip'] = $this->remoteAddress->getRemoteAddress();
            }else if($areaCode == Area::AREA_CRONTAB){
                $resource['area'] = Area::AREA_CRONTAB;
                $resource['cron_name'] = $this->registry->registry('current_cron_name');
            }
            if(isset($_SERVER['argv'])){
                foreach($_SERVER['argv'] as $_argv){
                    if($_argv == 'bin/magento'){
                        unset($resource['role']);
                        $resource['area'] = 'Command';
                        $resource['command_data'] = json_encode($_SERVER['argv']);
                        break;
                    }
                }
            }
            $resource['script'] = $_SERVER['SCRIPT_NAME'];
            $resource['url'] = $this->request->getRequestUri();
        }catch(\Exception $e){
            $resource['script'] = $_SERVER['SCRIPT_NAME'];
            $resource['url'] = $this->request->getRequestUri();
            $resource['error'] = $e->getMessage();
        }
        return $resource;
    }
}
