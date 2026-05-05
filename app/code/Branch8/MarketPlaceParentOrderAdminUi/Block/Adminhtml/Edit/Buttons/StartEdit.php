<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Block\Adminhtml\Edit\Buttons;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class SaveAndContinueButton
 */
class StartEdit extends GenericButton implements ButtonProviderInterface
{
    private ParentOrderManagementInterface $parentOrderManagement;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
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
        $data = [];
        if (!$this->parentOrderManagement->canEdit($this->getParentOrder())) {
            return [];
        };
        if ($this->getParentOrder()->canEdit()) {
            $editUrl = $this->startEditUrl();
            $data = [
                'label' => __('Edit'),
                'class' => 'action-default scalable edit primary',
                'on_click' => 'deleteConfirm(\'' . __(
                        'Parent Order and All Sub Orders will be canceled and a new one will be created instead.'
                    ) . '\', \'' . $editUrl . '\', {"data": {}})',
                'sort_order' => 20,
                'aclResource' => 'Branch8_MarketPlaceParentOrderAdminUi::edit',
            ];
        }
        return $data;
    }

    /**
     * Get delete url.
     *
     * @return string
     */
    public function startEditUrl()
    {
        return $this->getUrl('*/*/startEdit', ['id' => $this->getParentOrder()->getId()]);
    }
}
