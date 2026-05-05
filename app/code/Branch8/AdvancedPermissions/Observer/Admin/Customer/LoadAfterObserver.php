<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Observer\Admin\Customer;

use Branch8\AdvancedPermissions\Block\Adminhtml\Role\Tab\Seller;
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

        if ($this->request->getModuleName() !== 'customer') {
            return;
        }

        $rule = $this->helper->currentRule();

        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $observer->getCustomer();

        if (!$this->checkCustomerPermissions($rule, $customer)) {
            $this->helper->redirectHome();
        }
    }

    /**
     * @param \Amasty\Rolepermissions\Model\Rule $rule
     * @param \Magento\Customer\Model\Customer $customer
     *
     * @return bool
     */
    private function checkCustomerPermissions($rule, $customer)
    {
        if ($rule->getSellerAccessMode() == Seller::MODE_ANY || !$rule->getSellers() || !$customer->getId()) {
            return true;
        }
        $connection = $this->resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        $tblSalesOrder = $connection->getTableName('sales_order_grid');
        $tblMkOrder = $connection->getTableName('marketplace_orders');
        $result1 = $connection->fetchCol('SELECT customer_id FROM `'.$tblSalesOrder.'` as sog LEFT JOIN `'.$tblMkOrder.'` as mo ON sog.entity_id = mo.order_id WHERE seller_id IN ('.implode(',',$rule->getSellers()).')');

        return in_array($customer->getId(), $rule->getSellers()) || in_array($customer->getId(), $result1);
    }
}
