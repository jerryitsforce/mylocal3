<?php

declare(strict_types=1);

namespace Branch8\TicketApi\Model\Api;

use Magento\Framework\Webapi\Rest\Request;
use Branch8\TicketApi\Helper\Api;
use Branch8\TicketApi\Api\NotifyCancelInterface as ModelInterface;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\TicketApi\Model\TicketApiBrand\Source\Brand;
use Magento\Framework\Webapi\Rest\Response;

use Branch8\TicketApi\Helper\NotifyCancelHandler\Yoxi as YoxiHandler;
use Branch8\TicketApi\Helper\NotifyCancelHandler\GeneralNotify as GeneralNotifyHandler;
use Branch8\TicketApi\Helper\NotifyCancelHandler\FamilyBonusPin as FamilyBonusPinHandler;
use Branch8\TicketApi\Helper\NotifyCancelHandler\OpenHub as OpenHubHandler;

class NotifyCancel implements ModelInterface
{
    const LOG_FOLDER_NAME = "TicketApi/Api/NotifyCancel";

    /** @var Api */
    protected $apiHelper;

    /** @var Request */
    protected $request;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var Response */
    protected $response;

    /** @var YoxiHandler */
    protected $yoxiHandler;

    /** @var GeneralNotifyHandler */
    protected $generalNotifyHandler;

    /**
     * @var FamilyBonusPinHandler
     */
    protected $familyBonusPinHandler;

    /** @var OpenHubHandler */
    protected $openHubHandler;

    protected $returnCode;
    protected $returnMsg;
    protected $data;
    /**
     * @var \Branch8\EventTicket\Helper\Data
     */
    protected $eventTicketHelper;

    protected $serialHandle;

    protected $timezone;

    /**
     * @param Api $apiHelper
     * @param Request $request
     * @param HotaiCoreCommonHelper $hotaiCoreCommonHelper
     * @param Response $response
     * @param YoxiHandler $yoxiHandler
     * @param GeneralNotifyHandler $generalNotifyHandler
     * @param FamilyBonusPinHandler $familyBonusPinHandler
     * @param OpenHubHandler $openHubHandler
     * @param \Branch8\EventTicket\Helper\Data $eventTicketHelper
     */
    public function __construct(
        Api $apiHelper,
        Request $request,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        Response $response,

        YoxiHandler $yoxiHandler,
        GeneralNotifyHandler $generalNotifyHandler,
        FamilyBonusPinHandler $familyBonusPinHandler,
        OpenHubHandler $openHubHandler,
        \Branch8\EventTicket\Helper\Data $eventTicketHelper,
        \Branch8\EventTicket\Helper\SerialHandle $serialHandle,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->apiHelper             = $apiHelper;
        $this->request               = $request;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->response              = $response;

        $this->yoxiHandler           = $yoxiHandler;
        $this->generalNotifyHandler  = $generalNotifyHandler;
        $this->familyBonusPinHandler = $familyBonusPinHandler;
        $this->openHubHandler        = $openHubHandler;
        $this->eventTicketHelper = $eventTicketHelper;
        $this->serialHandle = $serialHandle;
        $this->timezone = $timezone;
    }

    public function handle()
    {
        date_default_timezone_set('Asia/Taipei');

        try {
            if (!$this->apiHelper->checkWhitelist()) {
                $returnMessage = "Allowed IP error.";

                return $this->sendResponse(
                    [
                        "ReturnCode" => Api::RETURN_CODE_DEFAULT_FAIL,
                        "ReturnMsg"  => $returnMessage
                    ],
                    [
                        "Title"          => $returnMessage,
                        "IP"             => $this->request->getClientIp(),
                        "Receive header" => $this->request->getHeaders()->toArray(),
                        "Receive body"   => $this->request->getBodyParams(),
                    ]
                );
            }

            if (!$this->apiHelper->checkDecryptArray()) {
                $returnMessage = "Decrypt error.";

                return $this->sendResponse(
                    [
                        "ReturnCode" => Api::RETURN_CODE_DEFAULT_FAIL,
                        "ReturnMsg"  => $returnMessage
                    ],
                    [
                        "Title"          => $returnMessage,
                        "IP"             => $this->request->getClientIp(),
                        "Receive header" => $this->request->getHeaders()->toArray(),
                        "Receive body"   => $this->request->getBodyParams(),
                    ]
                );
            }

            if (!$this->apiHelper->checkBrand()) {
                $returnMessage = "Receive brand error.";

                return $this->sendResponse(
                    [
                        "ReturnCode" => Api::RETURN_CODE_DEFAULT_FAIL,
                        "ReturnMsg"  => $returnMessage
                    ],
                    [
                        "Title"          => $returnMessage,
                        "IP"             => $this->request->getClientIp(),
                        "Receive header" => $this->request->getHeaders()->toArray(),
                        "Receive body"   => $this->request->getBodyParams(),
                        "Decrypt body"   => $this->apiHelper->getDecryptArray(),
                    ]
                );
            }

            if (!$this->apiHelper->checkBrandPermission()) {
                $returnMessage = "Allowed brand error.";

                return $this->sendResponse(
                    [
                        "ReturnCode" => Api::RETURN_CODE_DEFAULT_FAIL,
                        "ReturnMsg"  => $returnMessage
                    ],
                    [
                        "Title"          => $returnMessage,
                        "IP"             => $this->request->getClientIp(),
                        "Receive header" => $this->request->getHeaders()->toArray(),
                        "Receive body"   => $this->request->getBodyParams(),
                        "Decrypt body"   => $this->apiHelper->getDecryptArray(),
                    ]
                );
            }

            if (!$this->apiHelper->checkUsedTransactionNo()) {
                $returnMessage = "Transaction number error.";

                return $this->sendResponse(
                    [
                        "ReturnCode" => Api::RETURN_CODE_DEFAULT_FAIL,
                        "ReturnMsg"  => $returnMessage
                    ],
                    [
                        "Title"          => $returnMessage,
                        "IP"             => $this->request->getClientIp(),
                        "Receive header" => $this->request->getHeaders()->toArray(),
                        "Receive body"   => $this->request->getBodyParams(),
                        "Decrypt body"   => $this->apiHelper->getDecryptArray(),
                    ]
                );
            }

            $this->handleTicketCancelUpdate();

            return $this->sendResponse(
                [
                    "ReturnCode" => $this->returnCode,
                    "ReturnMsg"  => $this->returnMsg,
                    "Data"       => $this->data
                ]
            );
        } catch (\Exception $e) {
            return $this->sendResponse(
                [
                    "ReturnCode" => Api::RETURN_CODE_DEFAULT_FAIL,
                    "ReturnMsg"  => $e->getMessage(),
                    "Data"       => $this->data
                ],
                [
                    "Title"             => "Exception",
                    "Receive header"    => $this->request->getHeaders()->toArray(),
                    "Receive body"      => $this->request->getBodyParams(),
                    "Exception message" => $e->getMessage()
                ]
            );
        }
    }

    /**
     * @throws \Exception
     */
    protected function handleTicketCancelUpdate(): void
    {
        /**
         * Find and use the serial in event first
         * If not existed, call to product serial
         */
        $apiRequestData = $this->apiHelper->getDecryptArray();
        $serialTicketData = $this->eventTicketHelper->isEventTicket($apiRequestData, false);
        if($serialTicketData){
            $logData = $apiRequestData;
            $logData['ip'] = $this->request->getClientIp();
            $logData['action_type'] = 'cancel';
            $logData['created_at'] = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
            $logData['serial_number_id'] = $serialTicketData['serial_id'];
            $logData['serial_number'] = $apiRequestData['serialNo'];
            $logData['ticket_type'] = $apiRequestData['brand'];
            $logData['details'] = json_encode($apiRequestData);
            $logModel = $this->eventTicketHelper->addLogApiCheckSerial($logData);

            $this->serialHandle->cancelSerial($apiRequestData, $this->apiHelper->getMerchant());
            $this->returnCode = $this->serialHandle->getReturnCode();
            $this->returnMsg = $this->serialHandle->getReturnMsg();
            $this->data = $this->serialHandle->getReturnData()->toArray();

            $this->eventTicketHelper->updateLogApiCheckSerial($logModel, [
                'returnCode' => $this->returnCode,
                'returnMsg' => $this->returnMsg,
                'data' => $this->data
            ]);
        }else {
            switch ($this->apiHelper->getBrandCode()) {
                case Brand::BRAND_CODE_YOXI:
                    $this->yoxiHandler->handleCancelUpdate();
                    $this->returnCode = $this->yoxiHandler->getReturnCode();
                    $this->returnMsg = $this->yoxiHandler->getReturnMsg();
                    $this->data = $this->yoxiHandler->getReturnData()->toArray();
                    break;

                case Brand::BRAND_CODE_GENERAL_NOTIFY:
                    $this->generalNotifyHandler->handleCancelUpdate($this->apiHelper->getMerchant());
                    $this->returnCode = $this->generalNotifyHandler->getReturnCode();
                    $this->returnMsg = $this->generalNotifyHandler->getReturnMsg();
                    $this->data = $this->generalNotifyHandler->getReturnData()->toArray();
                    break;

                case Brand::BRAND_CODE_FAMILY_BONUS_PIN:
                    $this->familyBonusPinHandler->handleCancelUpdate();
                    $this->returnCode = $this->familyBonusPinHandler->getReturnCode();
                    $this->returnMsg = $this->familyBonusPinHandler->getReturnMsg();
                    $this->data = $this->familyBonusPinHandler->getReturnData()->toArray();
                    break;

                case Brand::BRAND_CODE_OPENHUB:
                    $this->openHubHandler->handleCancelUpdate($this->apiHelper->getMerchant());
                    $this->returnCode = $this->openHubHandler->getReturnCode();
                    $this->returnMsg = $this->openHubHandler->getReturnMsg();
                    $this->data = $this->openHubHandler->getReturnData()->toArray();
                    break;

                default:
                    throw new \Exception("Brand code not valid.");
            }
        }
    }

    protected function sendResponse(array $responseData, array $logData = null): void
    {
        if (!is_null($logData)) {
            $this->hotaiCoreCommonHelper->writeLog(
                json_encode($logData, JSON_UNESCAPED_SLASHES),
                self::LOG_FOLDER_NAME
            );
        }

        $this->response
            ->setHeader('Content-Type', 'application/json', true)
            ->setBody(json_encode($responseData))
            ->sendResponse();
    }
}
