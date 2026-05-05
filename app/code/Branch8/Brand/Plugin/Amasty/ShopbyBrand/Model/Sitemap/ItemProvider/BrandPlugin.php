<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       30/01/2026
 */

namespace Branch8\Brand\Plugin\Amasty\ShopbyBrand\Model\Sitemap\ItemProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
class BrandPlugin
{
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(

        ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param $subject
     * @param callable $process
     * @param $storeId
     * @return array
     */
    public function aroundGetItems($subject, callable $process, $storeId)
    {
        $disable = (bool)$this->scopeConfig->getValue('amshopby_brand/general/disable_brand_page');
        if ($disable) {
            return [];
        }
        return $process($storeId);
    }
}
