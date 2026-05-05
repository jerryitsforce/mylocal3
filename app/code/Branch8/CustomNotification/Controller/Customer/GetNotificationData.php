<?php

namespace Branch8\CustomNotification\Controller\Customer;

use Magenest\NotificationBox\Model\CustomerNotification;

class GetNotificationData extends \Magenest\NotificationBox\Controller\Customer\GetNotificationData
{
    /**
     * @param $customerId
     * @return array
     */
    public function getAllCustomerNotification($customerId)
    {
        try {
            $this->notificationCollection = $this->collectionFactory->create()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('status',CustomerNotification::STATUS_UNREAD)
                ->setPageSize($this->helper->getMaximumNotificationOnNotificationBox())
                ->setOrder('entity_id','DESC')
                ->getData();
            $maximumCharacter = $this->helper->getMaximumNotificationDescription();
            foreach ($this->notificationCollection as & $notification){
                if($notification['status']){
                    $notification['markAsRead'] = $this->helper->getThemeColor();
                }
                else{
                    $notification['markAsRead'] = $this->helper->getUnreadNotification();
                }
                $notification['icon'] = $this->helper->getImageByNotificationType($notification);
                if(isset($notification['description'])){
                    $notification['description'] = strlen($notification['description']) <= $maximumCharacter ?$notification['description']:mb_substr($notification['description'], 0, $maximumCharacter, 'UTF-8')."...";
                }
                $notification['created_at'] = $this->timezoneInterface->formatDateTime($notification['created_at'],2,2);
                $notification['redirect_url'] = $this->urlInterface->getUrl('notibox/handleNotification/viewNotification').'?id='.$notification['entity_id'];
            }
        }
        catch (\Exception $exception){
            $this->logger->error($exception->getMessage());
        }
        return $this->notificationCollection;
    }
}
