<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       30/01/2026
 */

namespace Branch8\Brand\Plugin\Amasty\ShopbyBrand\Controller\Index;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\Result\RedirectFactory;

class IndexPlugin
{
    private ScopeConfigInterface $scopeConfig;
    private RedirectFactory $resultRedirectFactory;

    /**
     * @param RedirectFactory $resultRedirectFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        RedirectFactory      $resultRedirectFactory,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param $subject
     * @param callable $proceed
     * @param ...$args
     * @return mixed
     */
    public function aroundExecute($subject, callable $proceed, ...$args)
    {
        $disable = (bool)$this->scopeConfig->getValue('amshopby_brand/general/disable_brand_page');
        if ($disable) {
            return $this->resultRedirectFactory->create()->setPath('404');
        }
        return $proceed(...$args);
    }
}
