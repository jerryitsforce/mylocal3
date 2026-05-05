<?php

namespace Branch8\WebkulMpBuyerSellerChatForParentOrder\Model\Services;

use Branch8\WebkulMpBuyerSellerChatForParentOrder\Model\ChatStatus;
use Magento\Framework\Math\Random;
use Magento\Sales\Model\Order\Item;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Webkul\Marketplace\Model\SaleslistFactory;
use Webkul\MpBuyerSellerChat\Api\CustomerDataRepositoryInterface;
use Webkul\MpBuyerSellerChat\Model\CustomerDataFactory;

class GetChatObjectByCustomerId
{
    const SELLER_TYPE = 'seller';
    const CUSTOMER_TYPE = 'customer';
    private $chatObjects = [];
    private CustomerDataRepositoryInterface $chatCustomerDataRepository;
    /**
     * @var CustomerDataFactory
     */
    private CustomerDataFactory $customerDataFactory;

    /**
     * @var Random
     */
    private Random $random;

    /**
     * @param CustomerDataFactory $customerDataFactory
     * @param CustomerDataRepositoryInterface $chatCustomerDataRepository
     * @param Random $random
     */
    public function __construct(
        CustomerDataFactory $customerDataFactory,
        CustomerDataRepositoryInterface $chatCustomerDataRepository,
        Random $random
    ) {
        $this->chatCustomerDataRepository = $chatCustomerDataRepository;
        $this->customerDataFactory = $customerDataFactory;
        $this->random = $random;
    }

    /**
     * @param int $customerId
     * @param string $type
     * @param bool $createIfNotExists
     * @param int $defaultStatus
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(
        int    $customerId,
        string $type,
        bool   $createIfNotExists = false,
        int    $defaultStatus = ChatStatus::ONLINE
    )
    {
        $key = $customerId . '_' . $type;
        if (isset($this->chatObjects[$key])) {
            return $this->chatObjects[$key];
        }
        $object = $this->chatCustomerDataRepository->getByCustomerId(
            $customerId, $type
        );
        if (!$object->getId() && $createIfNotExists) {
            $data = [
                'customer_id' => $customerId,
                'unique_id' => 'W' . $this->random->getRandomString(8),
                'chat_status' => $defaultStatus,
                'registered_as' => $type
            ];
            $object = $this->chatCustomerDataRepository->save(
                $this->customerDataFactory->create(['data' => $data])
            );
        }
        $this->chatObjects[$key] = $object;
        return $this->chatObjects[$key];
    }
}
