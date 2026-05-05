<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Customer\Block\Hotaipay;

use Branch8\HotaiPay\Model\CreditCard\Session;
use Magento\Framework\App\Http\Context as HttpContext;
use \Magento\Framework\Registry;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Index extends \Magento\Framework\View\Element\Template
{

    /**
     * @var Session
     */
    public $creditCardSession;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var \Branch8\Customer\Helper\Data
     */
    protected $helperData;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param Session $creditCardSession
     * @param HttpContext $httpContext
     * @param \Branch8\Customer\Helper\Data $helperData
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Session $creditCardSession,
        HttpContext $httpContext,
        Registry $registry,
        \Branch8\Customer\Helper\Data $helperData,
        CustomerRepositoryInterface $customerRepository,
        array $data = []
    ) {
        $this->creditCardSession = $creditCardSession;
        $this->httpContext = $httpContext;
        $this->registry = $registry;
        $this->helperData = $helperData;
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $data);
    }

    public function getCustomer(){
        if ($this->httpContext->getValue('customer_id')) {
            return $this->customerRepository->getById($this->httpContext->getValue('customer_id'));
        }
        return null;
    }

    /**
     * getCreditcardList
     *
     * @return void | array
     */
    public function getCreditcardList()
    {
        if(!($this->getCustomer())){
            return [];
        }

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

    public function getHotaiPayTopContent()
    {
        return $this->helperData->getHotaiPayTopContent();
    }

    public function getHotaiPayBottomContent()
    {
        return $this->helperData->getHotaiPayBottomContent();
    }

    public function getHotaiPayBottom()
    {
        return $this->helperData->getHotaiPayBottom();
    }

    public function getHotaiPayCustomerBottom()
    {
        return $this->helperData->getHotaiPayCustomerBottom();
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

    public function getEditUrl($id)
    {
        // get url to edit page
        return $this->getUrl('hotaipay/creditcard/edit?id='.$id);
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

