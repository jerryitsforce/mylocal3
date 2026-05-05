<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Block\Adminhtml\Edit\Buttons;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class SaveAndContinueButton
 */
class Reorder extends GenericButton implements ButtonProviderInterface
{
    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry           $registry
    )
    {
        parent::__construct($context, $registry);
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        $sendMailUrl = $this->getSendMailUrl();
        $data = [
            'label' => __('Reorder'),
            'class' => 'delete',
            'on_click' => 'deleteConfirm(\'' . __(
                    'Are you sure you want to to this action?'
                ) . '\', \'' . $sendMailUrl . '\', {"data": {}})',
            'sort_order' => 20,
            'aclResource' => 'Branch8_MarketPlaceParentOrderAdminUi::reorder',
        ];
        return $data;
    }

    /**
     * Get delete url.
     *
     * @return string
     */
    public function getSendMailUrl()
    {
        return $this->getUrl('*/*/reorder', ['id' => $this->getParentOrder()->getId()]);
    }
}
