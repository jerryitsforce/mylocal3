<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Block\Adminhtml\Edit\Buttons;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class SaveAndContinueButton
 */
class Cancel extends GenericButton implements ButtonProviderInterface
{
    private ParentOrderManagementInterface $parentOderManagment;

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
        $this->parentOderManagment = $parentOrderManagement;
        parent::__construct($context, $registry);
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        // if (!$this->parentOderManagment->canCancel($this->getParentOrder())) {
        //     return [];
        // }
        $cancelUrl = $this->getCancelUrl();
        $data = [
            'label' => __('Cancel'),
            'class' => 'delete',
            'on_click' => 'javascript:void(0)',
            'sort_order' => 20,
            'aclResource' => 'Branch8_MarketPlaceParentOrderAdminUi::cancel',
        ];
        return $data;
    }

    /**
     * Get delete url.
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->getUrl('*/*/cancelAjax', ['id' => $this->getParentOrder()->getId()]);
    }
}
