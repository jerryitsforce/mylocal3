<?php

namespace Branch8\MarketplaceSession\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;

class SessionManager
{
    // This route "/customer/account/loginPost" used to login all customers,
    // but this project used hotai_auth module to handle login for normal customers.
    // So we can treat it as seller login only route.
    const TARGET_ROUTES = [
        '/marketplace',
        '/gift-order/marketplace',
        '/mprmasystem',
        '/reqrma/rma',
        '/reqrma/seller',
        '/sellersubaccount',
        '/mpmassupload',
        '/customer/account/loginPost',
        '/b8marketplace',
        '/mppreorder'
    ];
    const CONFIG_PATH_SPLIT_SESSION = "marketplace_session/general/enable";
    const CONFIG_PATH_ADDITIONAL_ROUTES = "marketplace_session/general/additional_routes";
    const SESSION_NAME = 'MPSESSION';

    protected ScopeConfigInterface $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function beforeStart(
        \Magento\Framework\Session\SessionManager $subject
    ) {
        if (!$this->isEnabled()) {
            return;
        }

        if (!isset($_SERVER['REQUEST_URI'])) {
            return;
        }

        if (($this->hitTargetRoute() || $this->hitProductGridExportRoute()) && !$subject->isSessionExists()) {
            $subject->setName(self::SESSION_NAME);
        }
    }

    protected function isEnabled()
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_SPLIT_SESSION) == 1;
    }

    protected function hitTargetRoute()
    {
        // Get all routes to check (default + additional from config)
        $routesToCheck = self::TARGET_ROUTES;

        // Get additional routes from config
        $additionalRoutesConfig = $this->scopeConfig->getValue(self::CONFIG_PATH_ADDITIONAL_ROUTES);
        if ($additionalRoutesConfig) {
            $additionalRoutes = array_filter(
                array_map('trim', explode("\n", $additionalRoutesConfig)),
                function ($route) {
                    return !empty($route);
                }
            );
            $routesToCheck = array_merge($routesToCheck, $additionalRoutes);
        }

        // Remove duplicate routes
        $routesToCheck = array_unique($routesToCheck);

        // Check if current URI matches any of the routes
        foreach ($routesToCheck as $route) {
            if (str_starts_with($_SERVER['REQUEST_URI'], $route)) {
                return true;
            }
        }

        return false;
    }

    protected function hitProductGridExportRoute()
    {
        // Condition 1: URL path starts with /productgridexport
        $isProductGridExportPath = isset($_SERVER['REQUEST_URI']) && str_starts_with($_SERVER['REQUEST_URI'], '/productgridexport');

        // Condition 2: GET namespace is marketplace_products_listing
        $isMarketplaceNamespace = isset($_GET['namespace']) && $_GET['namespace'] === 'marketplace_products_listing';

        return $isProductGridExportPath && $isMarketplaceNamespace;
    }
}
