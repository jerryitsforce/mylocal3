<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Hopes\Cron;

use Branch8\Sales\Helper\Logger as LoggerHelper;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Branch8\Sales\Logger\Logger;
use Branch8\Hopes\Helper\Log as HopesLog;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderFactory;
use \Magento\Framework\App\State;
use \Magento\Sales\Model\OrderRepository;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommon;
use Branch8\Hopes\Model\RmaDetailsManagement;
use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Branch8\HifiSalesReport\Helper\Api as HifiApi;

class SendRmaToHopes
{
    /**
     * Log type value used by admin multiselect.
     */
    private const LOG_TYPE = 'SendRmaToHopes';

    const API_ROUTE_POST_HOPES = "api/Hope/PostPmcb2crtunordif";
    
    /** @var \Branch8\Sales\Logger\Logger $logger */
    private $logger;
    
    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory */
    protected $orderCollectionFactory;
    
    /** @var \Magento\Sales\Model\OrderRepository $orderRepository */
    protected $orderRepository;
    
    /** @var \Magento\Sales\Model\OrderFactory $order */
    protected $order;
    
    /** @var \Magento\Framework\App\State $state */
    protected $state;
    
    /** @var \Branch8\Sales\Helper\Logger $loggerhelper */
    protected $loggerhelper;
    
    /** @var \Magento\Sales\Api\Data\OrderInterface $orderInterface */
    protected $orderInterface;
    
    /** @var \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus */
    protected $updateOrderStatus;

    /** @var \Branch8\HotaiCore\Helper\Common $hotaiCoreCommon */
    protected $hotaiCoreCommon;

    /**
     * @var HopesLog
     */
    private HopesLog $hopesLog;
   
    /**
     * @var \Branch8\Hopes\Model\RmaDetailsManagement $rmaDetailsManagement
     */
    protected $rmaDetailsManagement;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;
    
    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    /**
     * @var array
     */
    protected $prepareData = [];
    
    /**
     * @var array $rmaId
     *
     */
    protected $rmaId = [];

    /** 
     * @var \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    private $resourceConnection;

    /**
     * @var \Branch8\HifiSalesReport\Helper\Api $hifiApi
     *
     */
    protected $hifiApi;

    /**
     * @param Logger $logger
     * @param CollectionFactory $orderCollectionFactory
     * @param OrderRepository $orderRepository
     * @param OrderFactory $order
     * @param State $state
     * @param LoggerHelper $loggerhelper
     * @param OrderInterface $orderInterface
     * @param UpdateOrderStatus $updateOrderStatus
     * @param HotaiCoreCommon $hotaiCoreCommon
     * @param RmaDetailsManagement $rmaDetailsManagement
     * @param HopesLog $hopesLog
     * @param ClientFactory $clientFactory
     * @param ResponseFactory $responseFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param ResourceConnection $resourceConnection
     * @param HifiApi $hifiApi
     */
    public function __construct(
        Logger $logger,
        CollectionFactory $orderCollectionFactory,
        OrderRepository $orderRepository,
        OrderFactory $order,
        State $state,
        LoggerHelper $loggerhelper,
        OrderInterface $orderInterface,
        UpdateOrderStatus $updateOrderStatus,
        HotaiCoreCommon $hotaiCoreCommon,
        RmaDetailsManagement $rmaDetailsManagement,
        HopesLog $hopesLog,
        ClientFactory $clientFactory,
        ResponseFactory $responseFactory,
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resourceConnection,
        HifiApi $hifiApi
    ) {
        $this->logger = $logger;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->order = $order;
        $this->state = $state;
        $this->loggerhelper = $loggerhelper;
        $this->orderInterface = $orderInterface;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->hotaiCoreCommon = $hotaiCoreCommon;
        $this->rmaDetailsManagement = $rmaDetailsManagement;
        $this->hopesLog = $hopesLog;
        $this->clientFactory = $clientFactory;
        $this->responseFactory = $responseFactory;
        $this->scopeConfig = $scopeConfig;
        $this->resourceConnection = $resourceConnection;
        $this->hifiApi = $hifiApi;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        // 阻擋非production環境發送請求
        if (getenv('ENV_TYPE') != 'production') {
            $this->hopesLog->log(self::LOG_TYPE, "-----Non-production environment, skip sending to Hopes----");
            return;
        }

        $this->hopesLog->log(self::LOG_TYPE, "------Start Of Cron-SentRmaToHopes-----");
    
        $collection = $this->rmaDetailsManagement->getRmaDetails();
        
        foreach($collection as $key => $data) {
            $subOrder = [
                "shipbrncd" => "SDEC",
                "b2CHDORDNO" => $data['B2CHDORDNO'],
                "orddt" => $data['ORDDT'],
                "currency" => $data['CURRENCY'],
                "b2CUSTID" => $data['B2CUSTID'],
                "invsheet" => $data['INVSHEET'],
                "rtnrecdt" => $data['ORDDT'],
                "rtntrans" => $data['RTNTRANS'],
                "rtninvono" => $data['RTNINVONO'],
                "b2CUSTNM" =>$data['B2CUSTNM'],
                "b2CUSTADDR" => $data['B2CUSTADDR'],
                "b2CUSTZIP"=> $data['B2CUSTZIP'],
                "b2CUSTTEL"=> $data['B2CUSTTEL'],
                "senddt" => date('Y-m-d H:i:s'),
                "promark" => '',
            ];

            $this->setData($subOrder, $data['ITEMS']);
        }

        /**
         * If there is no Hopes Data
         */
        if(!$this->prepareData) {
            $this->hopesLog->log(self::LOG_TYPE, "-----There is no hopes data, end Of Cron-SentRmaToHopes----");

            return;
        }

        $this->hopesLog->log(self::LOG_TYPE, '[Send Data] '. json_encode($this->prepareData, JSON_UNESCAPED_UNICODE));

        $response = $this->hifiApi->sendRequest(static::API_ROUTE_POST_HOPES, $this->prepareData);
        
        // Failed 
        if ($response!= $this->hifiApi::API_RESPONSE_CODE_SUCCESS) {
            $this->hopesLog->log(self::LOG_TYPE, '[Response] ' .$response);
        } else { //Success
            $this->setSendHopesFlag();
        }

        $this->hopesLog->log(self::LOG_TYPE, "-----End Of Cron-SentRmaToHopes----");
    }
    
    /**
     * setData
     *
     * @param array $subOrder
     * @param array $items
     * @return void
     */
    protected function setData(array $subOrder, array $items){
        
        $collection = $subOrder;
        foreach($items as $item) {
            $collection['frcd'] = $item['FRCD'];
            $collection['partno'] = $item['PARTNO'];
            $collection['iteM_NO'] = (int) $item['ITEM_NO'];
            $collection['ordqty'] = (int) $item['ORDQTY'];
            $collection['rtnordqty'] = (int) $item['RTNORDQTY'];
            $collection['rtnorddt'] = $item['RTNORDDT'];
            $collection['rtnrson'] = $item['RTNRSON'];
            $collection['rtntransno'] = $item['RTNTRANSNO'];

            $this->prepareData[] = $collection;
            $this->rmaId[] = $item['RMA_ID'];
        }
    
    }

    

    /**
     * Do API request with provided params
     *
     * @param string $uriEndpoint
     * @param array $params
     * @param string $requestMethod
     *
     * @return Response
     */
    private function doRequest(
        string $uriEndpoint,
        array $params = [],
        string $requestMethod = Request::HTTP_METHOD_POST
    ): Response {
        /** @var Client $client */
        $client = $this->clientFactory->create(['config' => [
            'base_uri' => $this->scopeConfig->getValue(
                \Branch8\HifiSalesReport\Helper\Api::CONFIG_PATH_API_DOMAIN
            )
        ]]);

        try {
            $this->hopesLog->log(self::LOG_TYPE, '[Send Params] '. json_encode($params, JSON_UNESCAPED_UNICODE));

            $response = $client->request(
                $requestMethod,
                $uriEndpoint,
                $params
            );
        } catch (GuzzleException $exception) {
            /** @var Response $response */
            $response = $this->responseFactory->create([
                'status' => $exception->getCode(),
                'reason' => $exception->getMessage()
            ]);

            $this->hopesLog->logException(self::LOG_TYPE, $exception);
        }

        return $response;
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Zend_Log_Exception
     */
    private function setSendHopesFlag(){
        if(empty($this->rmaId)){
            return;
        }

        $bind = [
            'is_sent_to_hopes' => true
        ];

        $rmaList = implode(',', $this->rmaId);

        $where = 'id in('.$rmaList.')';

        $connection = $this->resourceConnection->getConnection();

        $marketPlaceRmaDetail = $connection->getTableName('marketplace_rma_details');
        $num = $connection->update($marketPlaceRmaDetail, $bind, $where);
        
        $this->hopesLog->log(self::LOG_TYPE, '[Updated Record] '. $rmaList);

        $this->hopesLog->log(self::LOG_TYPE, '[Updated Record Num] '. $num);
    }
}
