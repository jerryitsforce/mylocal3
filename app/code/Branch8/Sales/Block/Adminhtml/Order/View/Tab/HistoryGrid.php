<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\Sales\Block\Adminhtml\Order\View\Tab;

use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Text\ListText;
use Magento\Sales\Model\Order;

/**
 *
 */
class HistoryGrid extends ListText implements TabInterface
{
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * Collection factory
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param array $data
     * @param AuthorizationInterface|null $authorization
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        array $data = [],
        ?AuthorizationInterface $authorization = null
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->authorization = $authorization ?? ObjectManager::getInstance()->get(AuthorizationInterface::class);
        parent::__construct($context, $data);
    }

    /**
     * Retrieve order model instance
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * @inheritdoc
     */
    public function getTabLabel()
    {
        return __('Comments History');
    }

    /**
     * @inheritdoc
     */
    /**
     * @inheritdoc
     */
    public function getTabTitle()
    {
        return __('Order History');
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isHidden()
    {
        return false;
    }
}
