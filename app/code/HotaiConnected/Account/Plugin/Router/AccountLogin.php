<?php
namespace HotaiConnected\Account\Plugin\Router;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Router\Base;

class AccountLogin
{
    /**
     * @param  Base             $subject
     * @param  RequestInterface $request
     * @return array
     */
    public function beforeMatch(
        Base $subject,
        RequestInterface $request
    ) {
        $identifier = trim($request->getPathInfo(), '/');
        if ($identifier === 'account/loginSuccess' && !$request->getModuleName()) {
            
            $params = $request->getParams();
            
            $request->setModuleName('hotaiconnected_account')
                ->setControllerName('LoginRedirect')
                ->setActionName('index')
                ->setDispatched(false);
            
            foreach ($params as $key => $value) {
                $request->setParam($key, $value);
            }
        }
        return [$request];
    }
}
