<?php

namespace Branch8\HotaiPay\Model\CreditCard;

use Branch8\HotaiPay\Api\CreditCardAjaxInterface;
use Branch8\HotaiPay\Model\Api\CreditCard;
use Branch8\HotaiPay\Model\CreditCard\Session as CreditCardSession;
use Branch8\HotaiPay\Model\CreditcardRepository;
use Branch8\HotaiPay\Helper\Log as HotaiPayLogHelper;
use Exception;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Session\SessionManager;
use Magento\Framework\Webapi\Rest\Request;

class Ajax implements CreditCardAjaxInterface
{
    public $coreSession;
    protected $creditCard;
    protected $request;
    public $creditCardSession;
    public $customerSession;
    public $creditcardRepository;
    private HotaiPayLogHelper $hotaiPayLogHelper;
    /**
     * __construct
     *
     * @param SessionManager $coreSession
     * @param CreditCard $creditCard
     * @param Request $request
     * @param CreditCardSession $creditCardSession
     * @param CustomerSession $customerSession
     * @param CreditcardRepository $creditcardRepository
     * @param HotaiPayLogHelper $hotaiPayLogHelper
     * @return void
     */
    public function __construct(
        SessionManager $coreSession,
        CreditCard $creditCard,
        Request $request,
        CreditCardSession $creditCardSession,
        CustomerSession $customerSession,
        CreditcardRepository $creditcardRepository,
        HotaiPayLogHelper $hotaiPayLogHelper
    ) {
        $this->creditcardRepository = $creditcardRepository;
        $this->customerSession = $customerSession;
        $this->request = $request;
        $this->creditCardSession = $creditCardSession;
        $this->coreSession = $coreSession;
        $this->creditCard = $creditCard;
        $this->hotaiPayLogHelper = $hotaiPayLogHelper;
    }

    /**
     * editFromAjax
     *
     * @return mixed
     */
    public function editFromAjax()
    {

        if (!$this->customerSession->isLoggedIn()) {
            $response = [
                'success' => false,
                'errMsg' => 'Customer is not loggedIn'];

            return json_encode($response);
        }

        $postBody = $this->request->getBodyParams();
        $customerId = $this->customerSession->getCustomer()->getId();

        $response = ['success' => false];

        try {

            if (isset($postBody['aliasName'])) {
                $payload = [
                    "TokenID" => (int) $postBody['tokenId'],
                    "AliasName" => (string) $postBody['aliasName'],
                ];

                $response = $this->creditCard->editCreditCardAliasName($payload);

                if (isset($response) && $response['success'] == false) {
                    throw new Exception('Failed to edit alias name from api.');
                }
            }

            $creditcardData = $this->creditCardSession->findCardInfoByTokenId($postBody['tokenId']);
            $this->creditcardRepository->checkCreditCardData($postBody, $customerId, $creditcardData);

            //update default card
            $this->creditcardRepository->setDefaultCard($postBody, $customerId, $this->isDefaultCard($postBody));

            //update alias name
            if (!empty($postBody['aliasName'])) {
                $this->creditcardRepository->updateCreditCard(
                    $postBody, 
                    $customerId, 
                    $this->creditcardRepository::ACTION_TYPE_UPDATE_ALIAS_NAME
                );
            }

            //unset credit card session
            $this->creditCardSession->unsCreditCardList();
            $this->creditCardSession->requestNewCardListAndReset();

            $response = ['success' => true];
        } catch (Exception $e) {
            $this->hotaiPayLogHelper->writeLog('[Exception] ' . $e->getMessage(), __CLASS__);
            $response = [
                'success' => false,
                'errMsg' => 'Failed to Edit Card.',
            ];
        }

        return json_encode($response);
    }

    /**
     * deleteFromAjax
     *
     * @return mixed
     */
    public function deleteFromAjax()
    {

        if (!$this->customerSession->isLoggedIn()) {
            $response = [
                'success' => false,
                'errMsg' => 'Customer is not loggedIn',
            ];

            return json_encode($response);
        }

        $postBody = $this->request->getBodyParams();
        $customerId = $this->customerSession->getCustomer()->getId();
        $response = ['success' => false];

        try {

            $payload = [
                "TokenID" => (int) $postBody['tokenId'],
            ];

            $response = $this->creditCard->deleteCreditCard($payload);

            //Delete Successfully
            if (isset($response) && $response['success'] == true) {
                $this->creditcardRepository->updateCreditCard(
                    $postBody, 
                    $customerId, 
                    $this->creditcardRepository::ACTION_TYPE_DELETE
                );

                $this->creditCardSession->unsCreditCardList();
                $this->creditCardSession->requestNewCardListAndReset();
            }
        } catch (Exception $e) {
            $this->hotaiPayLogHelper->writeLog('[Exception] ' . $e->getMessage(), __CLASS__);
            $response = [
                'success' => false,
                'errMsg' => 'Failed to Delete Card.',
            ];
        }

        return json_encode($response);
    }

    /**
     * isDefaultCard
     *
     * @param  mixed $cardInfo
     * @return bool
     */
    private function isDefaultCard($cardInfo): bool
    {
        return (!empty($cardInfo['isDefaultCard'])) ? true : false;
    }
}
