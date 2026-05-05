<?php

namespace Branch8\HotaiPay\Block\CreditCard;

use \Magento\Framework\View\Element\Template\Context;
use \Magento\Framework\Registry;
use Branch8\HotaiPay\Model\CreditCard\Session;
use Branch8\HotaiPay\Helper\CreditCard\BankCode;

class ListAction extends \Magento\Framework\View\Element\Template
{

    protected $registry;
    public $creditCardSession;
    public $data;

    public $param;
    public $response;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Session $creditCardSession,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->creditCardSession = $creditCardSession;
        parent::__construct($context, $data);
    }

    /**
     * getCreditcardList
     *
     * @return void | array
     */
    public function getCreditcardList()
    {
        //Get Credit Card List Data
        $creditcardList = $this->creditCardSession->getCardList();

        if (is_string($creditcardList)) {
            return [];
        }

        //If no credit card list data then return []
        if (!$creditcardList || $creditcardList['success'] == false) {
            return [];
        }

        //Collect credit card list data
        $result = [];
        foreach ($creditcardList['data'] as $creditcardData) {
            $key = $creditcardData['Id'];
            $result[$key]['CardType'] = $creditcardData['CardType'];
            $result[$key]['tokenId'] = $creditcardData['Id'];
            $result[$key]['BankDesc'] = $creditcardData['BankDesc'];
            $result[$key]['AliasName'] = $creditcardData['AliasName'];
            $result[$key]['CardNoMask'] = $creditcardData['CardNoMask'];
            $result[$key]['IsAvailable'] = $creditcardData['IsAvailable'];
            $result[$key]['IsOverwrite'] = $creditcardData['IsOverwrite'];
            
            if(isset($creditcardData['DefaultCard'])){
                $result[$key]['DefaultCard'] = $creditcardData['DefaultCard'];
            } else {
                $result[$key]['DefaultCard'] = false;
            }
            if(isset($creditcardData['bankName'])){
                $result[$key]['bankName'] = $creditcardData['bankName'];
            } else {
                $result[$key]['bankName'] = '';
            }
            if(isset($creditcardData['branchName'])){
                $result[$key]['branchName'] = $creditcardData['branchName'];
            } else {
                $result[$key]['branchName'] = '';
            }
        }
        return  $result;
    }

    /**
     * getParams
     *
     * @return void | array 
     */
    public function getParams()
    {
        return $this->registry->registry('params');
    }

    public function getOriginalParams()
    {
        return $this->getRequest()->getParams(); ;
    }


    /**
     * getErrorResponse
     *
     * @return void | array
     */
    public function getErrorResponse()
    {
        return $this->creditCardSession->getCardListErrorResponse();
    }

    public function unsetResponse()
    {
        return $this->creditCardSession->unsResponse();
    }

    public function getManuallyAddCardWarning()
    {
        $warning =  $this->creditCardSession->getManuallyAddCardWarning();
        if (is_string($warning)) {
            return $warning;
        }

        if (is_array($warning) && isset($warning[0])) {
            return $warning[0];
        }

        return '';
    }

    public function getFastAddCardWarning()
    {
        $warning =  $this->creditCardSession->getFastAddCardWarning();
        if (is_string($warning)) {
            return $warning;
        }

        if (is_array($warning) && isset($warning[0])) {
            return $warning[0];
        }
        
        return '';
    }
}
