<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Observer\Admin\Block;

use Branch8\AdvancedPermissions\Block\Adminhtml\Role\Tab\Block;
use Magento\Framework\Event\ObserverInterface;

class LoadAfterObserver implements ObserverInterface
{
    /**
     * @var \Amasty\Rolepermissions\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    private $authorization;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private \Magento\Framework\App\ResourceConnection $resource;

    public function __construct(
        \Amasty\Rolepermissions\Helper\Data $helper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->helper = $helper;
        $this->request = $request;
        $this->resource = $resource;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->request->getModuleName() == 'api') {
            return;
        }

        if ($this->request->getModuleName() !== 'cms') {
            return;
        }

        $rule = $this->helper->currentRule();

        /** @var \Magento\Cms\Model\Block $block */
        $block = $observer->getData('object');

        if (!$this->checkBlockPermissions($rule, $block)) {
            $this->helper->redirectHome();
        }
    }

    /**
     * @param \Amasty\Rolepermissions\Model\Rule $rule
     * @param \Magento\Customer\Model\Customer $customer
     *
     * @return bool
     */
    private function checkBlockPermissions($rule, $block)
    {
        if ($rule->getBlockAccessMode() == Block::MODE_ANY || !$rule->getBlocks() || !$block->getId()) {
            return true;
        }

        return in_array($block->getId(), $rule->getBlocks());
    }
}
