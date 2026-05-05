<?php

namespace Branch8\GiftToFriend\Helper;

class GiftBox extends \Magento\Framework\App\Helper\AbstractHelper
{
    const GIFTBOX_SESSION_LIFETIME = 'gift_order/general/giftbox_session_lifttime';
    const LOG_FOLDER_NAME = 'GiftToFriend/Helper/GiftBox';

    protected $customerSession;

    protected $timezone;

    protected $_conn;

    protected $resourceConnection;

    protected $transaction;

    protected $parentOrderDetailCollectionFactory;

    protected $hotaiCoreCommonHelper;

    protected $hotaiAuthService;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail\CollectionFactory $parentOrderDetailCollectionFactory,
        \Magento\Framework\DB\Transaction $transaction,
        \Branch8\HotaiCore\Helper\Common $hotaiCoreCommonHelper,
        \Branch8\HotaiAuth\Service\HotaiAuthService $hotaiAuthService
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->timezone = $timezone;
        $this->resourceConnection = $resourceConnection;
        $this->_conn = $this->resourceConnection->getConnection();
        $this->parentOrderDetailCollectionFactory = $parentOrderDetailCollectionFactory;
        $this->transaction = $transaction;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->hotaiAuthService = $hotaiAuthService;
    }

    public function setGuestSession($phoneNumber)
    {
        $sessionInMinite = $this->scopeConfig->getValue(self::GIFTBOX_SESSION_LIFETIME);
        $memberSeq = $this->hotaiAuthService->getHotaiOneIdByPhoneNumber($phoneNumber);
        $sessData = [
            'created_at' => $this->timezone->date()->add(new \DateInterval('PT'.$sessionInMinite.'M'))->format('Y-m-d H:i:s'),
            'phone_number' => $phoneNumber,
            'member_seq' => (string)$memberSeq
        ];
        if ($this->customerSession->getGuestGiftBoxSession()) {
            $this->customerSession->unsGuestGiftBoxSession();
        }
        $this->customerSession->setGuestGiftBoxSession($sessData);
    }

    public function getGuestCollection()
    {
        $guestSession = $this->customerSession->getGuestGiftBoxSession();
        if (!$guestSession) {
            return [];
        }
        $phoneNumber = $guestSession['phone_number'];
        $orderSelect = $this->_conn->select()
            ->distinct(true)
            ->from(['pr' => 'sales_parent_order_detail'])
            ->where('is_gift_order = ?', 1);
        $orderSelect->joinLeft(['address' => 'sales_parent_order_address'], 'pr.parent_id = address.parent_order_id', []);
        $orderSelect->where('telephone = "' . $phoneNumber . '"');

        return $orderSelect;
    }

    public function setLastAccessTime($recipientPhone)
    {
        $collextion = $this->parentOrderDetailCollectionFactory->create()
            ->addFieldToFilter('recipient_telephone', $recipientPhone);
        $updatedTime = $this->timezone->convertConfigTimeToUtc($this->timezone->date());

        $targetOrderIds = [];
        foreach ($collextion as $_order) {
            $targetOrderIds[] = $_order->getId();
        }

        if (empty($targetOrderIds)) {
            return;
        }

        // Using parentOrderDetail object with transaction object will overwrite the order item data,
        // so use resource connection instead.
        $connection = $this->_conn;
        $tableName = $this->resourceConnection->getTableName('sales_parent_order_detail');

        try {
            $connection->beginTransaction();
            $connection->update(
                $tableName,
                ['gift_last_access_time' => $updatedTime],
                ['entity_id IN (?)' => $targetOrderIds]
            );
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_GiftToFriend', 'giftbox_update_last_access')){
                $this->hotaiCoreCommonHelper->writeLog(json_encode([
                    "Title" => "Exception in setLastAccessTime",
                    "Target Order IDs" => implode(',', $targetOrderIds),
                    "Updated Time" => $updatedTime,
                    "Exception Message" => $e->getMessage(),
                ]), self::LOG_FOLDER_NAME);
            }
        }
    }

    public function getSalesPresentativeAddr()
    {
        $customerSession = $this->customerSession;
        if (!$customerSession->isLoggedIn()) {
            return null;
        }
        // $customerSession->setGiftRecipientInfor([
        //     "customerType"=> "0",
        //     "customerId"=> "A123456789",
        //     "name"=> "王大明",
        //     "phone"=> "0912345678",
        //     "email"=> "giftuser@example.com",
        //     "address"=> [
        //       "zipcode"=> "200",
        //       "city"=> "仁愛區",
        //       "district"=> "基隆市",
        //       "detail"=> "民生東路一段 100 號 6 樓"
        //     ],
        //     "sales"=> [
        //       "dealerCode"=> "A",
        //       "branchCode"=> "19",
        //       "sectionCode"=> "3",
        //       "salesCode"=> "82127",
        //       "name"=> "張大山"
        //     ]
        //   ]);
        $salePresentativeAddr = null;
        $salePresentativeInfor = $customerSession->getGiftRecipientInfor();
        
        if ($salePresentativeInfor && isset($salePresentativeInfor['address'])) {
            $salePresentativeAddr = [
                'name' => $salePresentativeInfor['name'],
                'phone' => $salePresentativeInfor['phone'],
                'email' => $salePresentativeInfor['email'],
                'address' => $salePresentativeInfor['address'],
                'note' => '',
                /** new version of recipient_infor does not have 'note', so set empty */
            ];
        }

        // if(!$salePresentativeAddr){
        //     //  $fake = json_decode('{
        //     //             "name": "王大明",
        //     //             "phone": "0955644981",
        //     //             "email": "giftuser@example.com",
        //     //             "address": {
        //     //                 "zipcode": "104",
        //     //                 "city": "台北市",
        //     //                 "district": "中山區",
        //     //                 "detail": "民生東路一段100號6樓"
        //     //             },
        //     //             "note": "和泰業代 - A1234"
        //     //             }', true);
        //     $fake = json_decode('{
        //         "name": "王大明",
        //         "phone": "0955644981",
        //         "email": "",
        //         "address": {
        //             "zipcode": "300",
        //             "city": "台北市",
        //             "district": "",
        //             "detail": "***"
        //         },
        //         "note": "和泰業代 - A1234"
        //     }', true);
        //     $salePresentativeAddr = $fake;
        // }
        
        return $salePresentativeAddr;
    }
}
