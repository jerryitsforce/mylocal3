<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\HotaiPay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use \Magento\Checkout\Model\Session;
use Branch8\HotaiPay\Logger\Api\Logger;
use Exception;
use Branch8\HotaiPay\Helper\Log as HotaiPayLogHelper;

class DataAssignObserver extends AbstractDataAssignObserver
{
    const TOKEN_ID = 'creditcard_token_id';
    const CREDITCARD_DATA = 'creditcard_detail_data';
    const TRANSACTION_RESULT = 'transaction_result';

    /**
     * @var array
     */
    protected $additionalInformationList = [
        self::TOKEN_ID,
        self::TRANSACTION_RESULT,
        self::CREDITCARD_DATA
    ];

    /** @var \Magento\Checkout\Model\Session $checkoutSession */
    protected $checkoutSession;

    private HotaiPayLogHelper $hotaiPayLogHelper;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        Session $checkoutSession,
        HotaiPayLogHelper $hotaiPayLogHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->hotaiPayLogHelper = $hotaiPayLogHelper;
    }

    /**
     * execute
     *
     * @param  mixed $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $method = $this->readMethodArgument($observer);
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        try {
            foreach ($this->additionalInformationList as $additionalInformationKey) {
                if (isset($additionalData[$additionalInformationKey])) {
    
                    if ($additionalInformationKey == self::CREDITCARD_DATA) {
                        $ccData = json_decode($additionalData[$additionalInformationKey]);
                        $this->checkoutSession->setData('ccData', $ccData);
                        $paymentInfo->addData(
                            [
                                'cc_type' => $ccData->CardType,
                                'cc_owner' => $ccData->MemberOneID,
                                'cc_last_4' => $ccData->CardNoMask !== null ?
                                    substr($ccData->CardNoMask, -4) : '',
                                'cc_number' => $ccData->CardNoMask
                            ]
                        );
                        continue;
                    }
    
                    $paymentInfo->setAdditionalInformation(
                        $additionalInformationKey,
                        $data->getAdditionalData($additionalInformationKey)
                    );
                }
            }
        } catch(Exception $e) {
            $this->hotaiPayLogHelper->writeLog($e->getMessage(), __CLASS__);
        }
        
    }
}
