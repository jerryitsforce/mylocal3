<?php

namespace Branch8\HotaiPay\Model\CreditCard;

use Branch8\HotaiPay\Helper\CreditCard\BankCode;
use Branch8\HotaiPay\Model\Api\CreditCard;
use Branch8\HotaiPay\Model\CreditcardRepository;
use Exception;
use Magento\Framework\Session\SessionManager;
use \Magento\Customer\Model\Session as CustomerSession;

class Session
{
    const CREDIT_CARD_DATA = 'creditCardData';
    const INIT = 'creditCardDataInit';
    const STATUS_DESC = 'StatusDesc';
    const EMPTY_DEFAULT_CARD = 0;

    /** @var \Magento\Framework\Session\SessionManager $coreSession */
    public $coreSession;

    /** @var \Branch8\HotaiPay\Model\Api\CreditCard $creditCard */
    protected $creditCard;

    /** @var \Branch8\HotaiPay\Model\CreditcardRepository $creditcardRepository */
    protected $creditcardRepository;

    /** @var \Magento\Customer\Model\Session $customerSession */
    protected $customerSession;

    public $resetDbColumn = false;

    /** @var \Branch8\HotaiPay\Helper\CreditCard\BankCode $bankCode */
    private $bankCode;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        SessionManager $coreSession,
        CreditCard $creditCard,
        CreditcardRepository $creditcardRepository,
        CustomerSession $customerSession,
        BankCode $bankCode
    ) {
        $this->coreSession = $coreSession;
        $this->creditCard = $creditCard;
        $this->creditcardRepository = $creditcardRepository;
        $this->customerSession = $customerSession;
        $this->bankCode = $bankCode;
    }

    /**
     * setCardList
     *
     * @param  mixed $data
     * @return void
     */
    public function setCardList($data)
    {
        if ($this->coreSession->getData(self::CREDIT_CARD_DATA)) {
            $this->unsCreditCardList();
        }
        $this->coreSession->setData(self::CREDIT_CARD_DATA, $data);
    }

    /**
     * getCardList
     *
     * @return void | array
     */
    public function getCardList()
    {
        // //Default: Get credit card from session
        // $creditCardData = $this->coreSession->getData(self::CREDIT_CARD_DATA);

        // if (isset($creditCardData)) {
        //     return $creditCardData;
        // }

        //call credit card data every time
        $this->requestNewCardListAndReset();
        return $this->coreSession->getData(self::CREDIT_CARD_DATA);
    }

    /**
     * getCardListErrorResponse
     *
     * @param  mixed $init
     * @return void | array
     */
    public function getCardListErrorResponse()
    {
        $creditCardData = $this->coreSession->getData(self::CREDIT_CARD_DATA);

        if (is_string($creditCardData)) {
            return;
        }

        if ($creditCardData && $creditCardData['success'] == false) {
            $this->coreSession->setData(
                "Response",
                [
                    Session::STATUS_DESC => $creditCardData['error'],
                ]
            );
        }

        return $this->coreSession->getData("Response");
    }

    /**
     * unsResponse
     *
     * @return void
     */
    public function unsResponse()
    {
        $this->coreSession->unsetData("Response");
    }

    /**
     * init
     *
     * @return void
     */
    public function setInit()
    {
        $this->coreSession->setData(self::INIT, true);
    }

    /**
     * unsInit
     *
     * @return void
     */
    public function unsInit()
    {
        $this->coreSession->unsetData(self::INIT);
    }

    /**
     * unsCreditCardList
     *
     * @return void
     */
    public function unsCreditCardList()
    {
        $this->coreSession->unsetData(self::CREDIT_CARD_DATA);
    }

    /**
     * requestNewCardListAndReset
     *
     * @return void
     */
    public function requestNewCardListAndReset()
    {
        $crediDardData = $this->creditCard->getCreditCardList(['Bin' => true]);
        $crediDardData = $this->setDefaultCardParams($crediDardData);
        $this->setCardList($crediDardData);
    }

    /**
     * setUpdateDbColumn
     *
     * @param  mixed $bool
     * @return void
     */
    public function resetDbColumn($bool)
    {
        $this->resetDbColumn = $bool;
    }

    /**
     * setDefaultCardParams
     *
     * @param  mixed $crediDardData
     * @return void | array
     */
    private function setDefaultCardParams($crediDardData)
    {
        if (!$crediDardData || !isset($crediDardData['data'])) {
            return $crediDardData;
        }

        $customerId = $this->customerSession->getCustomer()->getId();

        //If canot get customer session data, then return.
        if(!$customerId) {
            return $crediDardData;
        }

        //Set default empty default card
        $crediDardData['DefaultCardTokenId'] = self::EMPTY_DEFAULT_CARD;

        $defaultCardList = $this->creditcardRepository->getDefaultCardList($customerId);
        $memberCardList = $this->creditcardRepository->getMemberCardList($customerId);

        if ($defaultCardList) {
            $crediDardData['DefaultCardTokenId'] = $defaultCardList[0];
        }

        if ($this->resetDbColumn || !$memberCardList) {
            $this->creditcardRepository->resetCreditCardInfo($customerId, $crediDardData);
        }

        //Find default Card Info
        foreach ($crediDardData['data'] as $key => $data) {
            if ($defaultCardList) {
                if ($data['Id'] == $defaultCardList[0]) {
                    $crediDardData['data'][$key]['DefaultCard'] = true;
                    continue;
                }
            }

            $crediDardData['data'][$key]['DefaultCard'] = false;

            //Set up bank name info
            $binInfoCode = '';
            if (isset($data['BinInfo'])) {
                $binInfoCode = $data['BinInfo']['Code'];
            }
            
            $bankInfo = $this->getBankNameInfo($binInfoCode);
            $crediDardData['data'][$key][$this->bankCode::BANK_NAME] = $bankInfo[$this->bankCode::BANK_NAME];
            $crediDardData['data'][$key][$this->bankCode::BRANCH_NAME] = $bankInfo[$this->bankCode::BRANCH_NAME];

        }

        return $crediDardData;
    }

    /**
     * getBankNameInfo
     *
     * @param  mixed $code
     * @return array
     */
    private function getBankNameInfo($code)
    {
        return $this->bankCode->getBankInfo($code);
    }

    public function findCardInfoByTokenId($tokenId)
    {
        $crediDardData = $this->getCardList();
        foreach ($crediDardData['data'] as $key => $data) {

            if ($data['Id'] == $tokenId) {
                return $data;
            }
        }
    }
    
    /**
     * getBankNameInfo
     */
    public function getManuallyAddCardWarning()
    {
        return $this->creditCard->getManuallyAddCardWarning();
    }

    /**
     * getFastAddCardWarning
     */
    public function getFastAddCardWarning()
    {
        return $this->creditCard->getFastAddCardWarning();
    }
}
