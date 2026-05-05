<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Block\Adminhtml\Edit\Buttons;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class SaveAndContinueButton
 */
class UnHold extends GenericButton implements ButtonProviderInterface
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
        if (!$this->parentOrderManagement->canUnHold($this->getParentOrder())) {
            return [];
        };
        $unHoldUrl = $this->getUnHoldUrl();
        $data = [
            'label' => __('UnHold'),
            'class' => 'delete',
            'on_click' => 'deleteConfirm(\'' . __(
                    'This will mark all sub-orders into Un-Hold status, are you sure you want to do this?'
                ) . '\', \'' . $unHoldUrl . '\', {"data": {}})',
            'sort_order' => 20,
            'aclResource' => 'Branch8_MarketPlaceParentOrderAdminUi::unHold',
        ];
        return $data;
    }

    /**
     * Get delete url.
     *
     * @return string
     */
    public function getUnHoldUrl()
    {
        return $this->getUrl('*/*/unHold', ['id' => $this->getParentOrder()->getId()]);
    }
}
