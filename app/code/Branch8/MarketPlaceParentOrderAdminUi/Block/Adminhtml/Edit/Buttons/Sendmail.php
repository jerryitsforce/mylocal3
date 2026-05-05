<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Block\Adminhtml\Edit\Buttons;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class SaveAndContinueButton
 */
class SendMail extends GenericButton implements ButtonProviderInterface
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
            'label' => __('Send Mail'),
            'class' => 'delete',
            'on_click' => 'deleteConfirm(\'' . __(
                    'Are you sure you want to send an order email to customer?'
                ) . '\', \'' . $sendMailUrl . '\', {"data": {}})',
            'sort_order' => 20,
            'aclResource' => 'Branch8_MarketPlaceParentOrderAdminUi::email',
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
        return $this->getUrl('*/*/sendMail', ['id' => $this->getParentOrder()->getId()]);
    }
}
