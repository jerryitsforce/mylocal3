<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\HotaiPay\Model\Ui;

use Branch8\HotaiPay\Helper\Data;
use Magento\Checkout\Model\ConfigProviderInterface;
use Branch8\HotaiPay\Service\HotaiPay;
use Branch8\HotaiPay\Model\CreditCard\Session;
use Magento\Framework\App\RequestInterface;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var HotaiPay
     */
    protected $hotaiPayService;
    const CODE = 'hotaipay';

    /**
     * @var Session
     */
    public $creditCardSession;

    /**
     * @var \Branch8\HotaiPay\Helper\Data
     */
    protected $hotaiPayHelper;

    /**
     * @var RequestInterface
     */
    protected $request;

    protected $_isInitializeNeeded = true;

    /**
     * @param HotaiPay $hotaiPayService
     * @param Session $creditCardSession
     * @param Data $hotaiPayHelper
     * @param RequestInterface $request
     */
    public function __construct(
        HotaiPay $hotaiPayService,
        Session $creditCardSession,
        \Branch8\HotaiPay\Helper\Data $hotaiPayHelper,
        RequestInterface $request,
    ) {
        $this->hotaiPayService = $hotaiPayService;
        $this->creditCardSession = $creditCardSession;
        $this->hotaiPayHelper = $hotaiPayHelper;
        $this->request = $request;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        if (str_contains($this->request->getRequestUri(), 'checkout/cart')) return [];

        $creditcard = [];
        $creditCardDetailData = [];

        if(!$this->hotaiPayHelper->getToken()){
            return [
                'payment' => [
                    self::CODE => [
                        'creditCardLists' => $creditcard,
                        'creditCardDetailData' => $creditCardDetailData
                    ]
                ]
            ];
        }

        $creditcardList = $this->creditCardSession->getCardList();
        if (isset($creditcardList['data'])) {
            foreach ($creditcardList['data'] as $creditcardData) {
                $defaultCardText = $this->getCardOrderText($creditcardList, $creditcardData['DefaultCard']);
                $creditcard[$creditcardData['Id']] = $defaultCardText . $creditcardData['CardNoMask'] . "　" . $creditcardData['BankDesc'];
                $creditCardDetailData[$creditcardData['Id']] = $creditcardData;
            }
        }

        return [
            'payment' => [
                self::CODE => [
                    'creditCardLists' => $creditcard,
                    'creditCardDetailData' => $creditCardDetailData
                ]
            ]
        ];
    }

    /**
     * Retrieve assoc array of checkout configuration
     * skip card expire
     * @return array
     */
    public function getConfigExcludeExpire()
    {

        $creditcard = [];
        $creditCardDetailData = [];

        if(!$this->hotaiPayHelper->getToken()){
            return [
                'payment' => [
                    self::CODE => [
                        'creditCardLists' => $creditcard,
                        'creditCardDetailData' => $creditCardDetailData
                    ]
                ]
            ];
        }

        $creditcardList = $this->creditCardSession->getCardList();
        if (isset($creditcardList['data'])) {
            foreach ($creditcardList['data'] as $creditcardData) {
                if(!$creditcardData['IsExpired']) {
                    $defaultCardText = $this->getCardOrderText($creditcardList, $creditcardData['DefaultCard']);
                    $creditcard[$creditcardData['Id']] = $defaultCardText . $creditcardData['CardNoMask'] . "　" . $creditcardData['BankDesc'];
                    $creditCardDetailData[$creditcardData['Id']] = $creditcardData;
                }
            }
        }

        return [
            'payment' => [
                self::CODE => [
                    'creditCardLists' => $creditcard,
                    'creditCardDetailData' => $creditCardDetailData
                ]
            ]
        ];
    }

    /**
     * getCardOrderText
     *
     * @param  mixed $creditcardList
     * @param  mixed $defaultCard
     * @return void | string
     */
    private function getCardOrderText($creditcardList, $defaultCard)
    {

        if ($creditcardList['DefaultCardTokenId'] === Session::EMPTY_DEFAULT_CARD) {
            return '';
        }

        if ($defaultCard === true) {
            return "Default Card　";
        }

        return "Sub Card　";
    }
}
