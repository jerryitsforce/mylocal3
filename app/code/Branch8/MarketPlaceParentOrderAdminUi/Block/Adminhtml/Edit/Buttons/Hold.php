<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Block\Adminhtml\Edit\Buttons;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class SaveAndContinueButton
 */
class Hold extends GenericButton implements ButtonProviderInterface
{
    private ParentOrderManagementInterface $parentOrderManagement;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ParentOrderManagementInterface $parentOrderManagement
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry           $registry,
        ParentOrderManagementInterface        $parentOrderManagement
    )
    {
        $this->parentOrderManagement = $parentOrderManagement;
        parent::__construct($context, $registry);
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        if (!$this->parentOrderManagement->canHold($this->getParentOrder())) {
            return [];
        };
        $cancelUrl = $this->getHoldUrl();
        $data = [
            'label' => __('Hold'),
            'class' => 'delete',
            'on_click' => 'deleteConfirm(\'' . __(
                    'This will mark all sub-orders into Hold status, are you sure you want to do this?'
                ) . '\', \'' . $cancelUrl . '\', {"data": {}})',
            'sort_order' => 20,
            'aclResource' => 'Branch8_MarketPlaceParentOrderAdminUi::hold',
        ];
        return $data;
    }

    /**
     * Get delete url.
     *
     * @return string
     */
    public function getHoldUrl()
    {
        return $this->getUrl('*/*/hold', ['id' => $this->getParentOrder()->getId()]);
    }
}
