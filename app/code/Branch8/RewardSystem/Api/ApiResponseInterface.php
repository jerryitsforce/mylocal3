<?php
namespace Branch8\RewardSystem\Api;

interface ApiResponseInterface
{
    const IS_SUCCESS = 'is_success';

    const EVENT_ID = 'event_id';

    const CUSTOMER_IDENTIFY = 'customer_identify';

    const PARTNER_IDENTIFY = 'partner_identify';

    const CREATE_AT = 'created_at';

    const MESSAGE = 'message';

    /**
     * @return int
     */
    public function getIsSuccess();

    /**
     * @return int
     */
    public function getEventId();

    /**
     * @return string
     */
    public function getCustomerIdentify();
    
    /**
     * @return string
     */
    public function getCreatedAt();
    /**
     * @param int $eventId
     * @return $this
     */
    public function setEventId(int $eventId);
    /**
     * @param string $customerIdentify
     * @return $this
     */
    public function setCustomerIdentify(string $customerIdentify);
    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt);

    /**
     * @return string
     */
    public function getPartnerIdentify();

    /**
     * @param string $partnerIdentify
     * @return $this
     */
    public function setPartnerIdentify(string $partnerIdentify);

    /**
     * @param int $isSuccess
     * @return $this
     */
    public function setIsSuccess(string $isSuccess);

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message);
}
