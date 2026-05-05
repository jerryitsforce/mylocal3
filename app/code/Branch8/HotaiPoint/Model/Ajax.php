<?php

namespace Branch8\HotaiPoint\Model;

use Branch8\HotaiPoint\Api\AjaxInterface;
use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Branch8\HotaiPoint\Helper\Common as CommonHelper;
use Branch8\HotaiPoint\Helper\Data as PointHelper;
use Branch8\HotaiPoint\Model\Config\Source\LogOption;
use Exception;
use \Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use \Magento\Framework\Webapi\Rest\Request;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommon;
use Branch8\HotaiAuth\Service\HotaiAuthService;

class Ajax implements AjaxInterface
{
    const TRANSFER_LOG_PATH = 'HotaiPoint/Actions/Transfer/Ajax';
    const TRANSFERSMS_LOG_PATH = 'HotaiPoint/Actions/GetMemberOTPSend/Ajax';
    const REGISTER_LOG_PATH = 'HotaiPoint/Actions/Register/Ajax';
    private const DEBUG_LOG_OPTION = LogOption::LOG_AJAX;

    /*
     * @var ApiHelper
     */
    protected $apiHelper;

    /*
     * @var PointHelper
     */
    protected $pointHelper;

    /*
     * @var CustomerSession
     */
    protected $customerSession;

    /*
     * @var HotaiCoreCommon
     */
    protected $hotaiCoreCommon;
    protected $commonHelper;

    /*
     * @var CustomerCollectionFactory
     */
    protected $customerCollectionFactory;

    /*
     * @var Request
     */
    protected $request;

    /*
     * @var HotaiAuthService
     */
    protected $hotaiAuthService;

    /**
     * @param ApiHelper $apiHelper
     * @param PointHelper $pointHelper
     * @param CustomerSession $customerSession
     * @param HotaiCoreCommon $hotaiCoreCommon
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param Request $request
     * @param HotaiAuthService $hotaiAuthService
     */
    public function __construct(
        ApiHelper $apiHelper,
        CommonHelper $commonHelper,
        PointHelper $pointHelper,
        CustomerSession $customerSession,
        HotaiCoreCommon $hotaiCoreCommon,
        CustomerCollectionFactory $customerCollectionFactory,
        Request $request,
        HotaiAuthService $hotaiAuthService
    ) {
        $this->apiHelper = $apiHelper;
        $this->commonHelper = $commonHelper;
        $this->pointHelper = $pointHelper;
        $this->customerSession = $customerSession;
        $this->hotaiCoreCommon = $hotaiCoreCommon;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->request = $request;
        $this->hotaiAuthService = $hotaiAuthService;
    }

    /**
     * transferPointAjax
     *
     * @return mixed
     */
    public function transferPointAjax(){
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
            $this->writeLog('----Start----');
            $this->writeLog('body: ' . json_encode($postBody));
            $this->writeLog('customerId: ' . $customerId);

            if (isset($postBody['transferTo']) && isset($postBody['transferPoint']) && isset($postBody['transferCheckCode'])) {
                $toCustomerMemberSeq = $this->getCustomerMemberSeqByPhoneNumberUsingApi($postBody['transferTo']);

                if ($toCustomerMemberSeq) {
                    $this->writeLog('toCustomerMemberSeq: ' . $toCustomerMemberSeq);
                    $this->writeLog('toCustomerMemberAccount: ' . $postBody['transferTo']);

                    $response = $this->apiHelper->requestApiTransferPoint($customerId, $toCustomerMemberSeq, $postBody['transferTo'], $postBody['transferPoint'], $postBody['transferCheckCode']);

                    $this->writeLog('transferPointAjax response: ' . json_encode($response));

                    // if (isset($response) && $response['success'] == false) {
                    //     throw new Exception('Failed to edit alias name from api.');
                    // }
                } else {
                    $response = [
                        'success' => false,
                        'errMsg' => '查無轉入對象帳號。',
                    ];
                    return json_encode($response);
                }
            } else {
                throw new Exception('無效的請求參數。');
            }

            $response = ['success' => true];
        } catch (Exception $e) {
            $this->writeLog('Error: ' . $e->getMessage());
            $errorMsg = json_decode($e->getMessage(), true);
            $returnMsg = json_decode($errorMsg['Response string after decrypt'], true);
            $response = [
                'success' => false,
                'errMsg' => $returnMsg['returnMsg'] ?? $e->getMessage()?? '點數轉移失敗',
            ];
        }

        return json_encode($response);
    }

    /**
     * getMemberOTPSendAjax
     *
     * @return mixed
     */
    public function getMemberOTPSendAjax(){
        if (!$this->customerSession->isLoggedIn()) {
            $response = [
                'success' => false,
                'errMsg' => 'Customer is not loggedIn'];

            return json_encode($response);
        }

        $postBody = $this->request->getBodyParams();
        $customerId = $this->customerSession->getCustomer()->getId();

        if( $postBody['transferTo'] === $this->customerSession->getCustomer()['phone_number'] ){
            $response = [
                'success' => false,
                'errMsg' => '轉出與轉入不可為同一人'
            ];

            return json_encode($response);
        }

        $response = ['success' => false];

        try {
            $this->writeLog('----Start----', 'getMemberOTPSend');
            $this->writeLog('customerId: ' . $customerId, 'getMemberOTPSend');

            $apiResponse = $this->apiHelper->requestApiGetMemberOTPSend($customerId);

            $this->writeLog('transferSMSAjax response: ' . json_encode($apiResponse), 'getMemberOTPSend');

            $response = [
                'success' => true,
                'returnMsg' => $apiResponse['returnMsg'] ?? null
            ];
        } catch (Exception $e) {
            $this->writeLog('Error: ' . $e->getMessage(), 'getMemberOTPSend');
            $errorMsg = json_decode($e->getMessage(), true);
            $exceedinglimit = json_decode($errorMsg['Response string after decrypt'], true);
            $response = [
                'success' => false,
                'errMsg' => $exceedinglimit['returnMsg']  ?? $e->getMessage() ?? '簡訊驗證碼取得失敗',
                'exceedinglimit' => $exceedinglimit['returnCode'] === '0342',
                'returnCode' => $exceedinglimit['returnCode']
            ];
        }

        return json_encode($response);
    }

    /**
     * registerPointAjax
     *
     * @return mixed
     */
    public function registerPointAjax(){
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
            $this->writeLog('----Start--- ', 'register');
            $this->writeLog('body: ' . json_encode($postBody), 'register');
            $this->writeLog('customerId: ' . $customerId, 'register');
            if (isset($postBody['serialNumber'])) {
                $response = $this->apiHelper->requestApiUsingCoupon($customerId, $postBody['serialNumber']);

                $this->writeLog('Response: ' . json_encode($response), 'register');

                // if (isset($response) && $response['success'] == false) {
                //     throw new Exception('Failed to edit alias name from api.');
                // }
            } else {
                throw new Exception('無效的請求參數。');
            }

            $response = ['success' => true];
        } catch (Exception $e) {
            $this->writeLog('Error: ' . $e->getMessage(), 'register');
            $response = [
                'success' => false,
                'errMsg' => $e->getMessage()?? '顧客尚未登入，無法登錄序號。',
            ];
        }

        return json_encode($response);
    }

    /**
     * Get customer by custom attribute phone_number
     *
     * @param string $phoneNumber
     * @return \Magento\Customer\Model\Customer|null
     */
    public function getCustomerByPhoneNumber(string $phoneNumber)
    {
        $customerCollection = $this->customerCollectionFactory->create();
        $customerCollection->addAttributeToSelect('*')
            ->addAttributeToFilter('phone_number', $phoneNumber)
            ->setPageSize(1);

        $customer = $customerCollection->getFirstItem();

        if ($customer && $customer->getId()) {
            return $customer;
        }

        return null;
    }

    public function getCustomerMemberSeqByPhoneNumberUsingApi(string $phoneNumber): ?string
    {
        return $this->hotaiAuthService->getHotaiOneIdByPhoneNumber($phoneNumber);
    }

    /**
     * 寫入log
     *
     * @param string $message
     * @return void
     */
    private function writeLog(string $message, string $type = ''): void
    {
        $logFile = $type == 'register' ? self::REGISTER_LOG_PATH : ($type == 'getMemberOTPSend' ? self::TRANSFERSMS_LOG_PATH : self::TRANSFER_LOG_PATH);
        $this->commonHelper->writeLogIfEnabled(
            $message,
            $logFile,
            self::DEBUG_LOG_OPTION
        );
    }
}
