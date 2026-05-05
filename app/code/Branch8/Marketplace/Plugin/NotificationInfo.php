<?php

namespace Branch8\Marketplace\Plugin;

use Webkul\Marketplace\Model\Notification;

class NotificationInfo{
    /**
     * @var \Branch8\Marketplace\Model\CustomNotificationFactory
     */
    protected $customNotificationFactory;

    protected $_escaper;

    protected $blockFactory;

    /**
     * @param \Branch8\Marketplace\Model\CustomNotificationFactory $customNotificationFactory
     */
    public function __construct(
        \Branch8\Marketplace\Model\CustomNotificationFactory $customNotificationFactory,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\View\Element\TemplateFactory $blockFactory

    ){
        $this->customNotificationFactory = $customNotificationFactory;
        $this->_escaper = $escaper;
        $this->blockFactory = $blockFactory;
    }

    /**
     * @param $subject
     * @param $result
     * @param $dataSource
     * @return mixed
     */
    public function afterPrepareDataSource($subject, $result, $dataSource){
        if (isset($result['data']['items'])) {
            $fieldName = $subject->getData('name');
            foreach ($result['data']['items'] as &$item) {
                if ($item['type'] == \Webkul\Marketplace\Model\Notification::TYPE_CUSTOM) {

                    $customNotification = $this->customNotificationFactory->create()->load($item['notification_id']);
                    $customNofiticationTxt = $this->_escaper->escapeHtml($customNotification->getDescription());
                    $item['details'] = $this->blockFactory->create()
                        ->setData('description', $customNofiticationTxt)
                    ->setTemplate('Branch8_Marketplace::custom_notify.phtml')->toHtml();
                }
            }
        }
        return $result;
    }

}