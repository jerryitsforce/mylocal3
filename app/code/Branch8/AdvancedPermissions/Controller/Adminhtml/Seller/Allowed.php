<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Controller\Adminhtml\Seller;

use Magento\Backend\App\Action;
use Magento\Framework\ObjectManagerInterface;

class Allowed extends Action
{
    /**
     * @var \Magento\Framework\View\Result\LayoutFactory
     */
    protected $resultLayoutFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Amasty\Rolepermissions\Model\RuleFactory
     */
    private $ruleFactory;

    public function __construct(
        Action\Context $context,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Amasty\Rolepermissions\Model\RuleFactory $ruleFactory,
        \Magento\Framework\Registry $registry
    ) {
        parent::__construct($context);

        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->_coreRegistry = $registry;
        $this->ruleFactory = $ruleFactory;
    }

    public function execute()
    {
        /** @var \Amasty\Rolepermissions\Model\Rule $rule */
        $rule = $this->ruleFactory->create();

        if ($rid = (int) $this->_request->getParam('rid')) {
            $rule->load($rid, 'role_id');
        }

        $this->_coreRegistry->register('amrolepermissions_current_rule', $rule);

        return $this->resultLayoutFactory->create();
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_User::acl_roles');
    }
}
