<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Hopes\Model;

use Branch8\Hopes\Helper\Logger as LoggerHelper;
use Branch8\Hopes\Logger\Logger;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder;
use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Framework\Webapi\Rest\Response;

class AbstractModel
{

    const LOG_PATH = "base/";
    const ERR_MSG  = "ErrorMsg";

    /** @var \Magento\Framework\Webapi\Rest\Request $request */
    public $request;

    /** @var \Branch8\Hopes\Logger\Logger $logger */
    private $logger;

    /** @var \Branch8\Hopes\Helper\Logger $loggerhelper */
    private $loggerhelper;

    /** @var \Magento\Framework\Webapi\Rest\Response $response */
    public $response;

    /** @var array $err */
    public $err = [];

    /** @var ParentOrder */
    protected $parentOrder;

    /** @var mixed $parentOrderInterface */
    protected $parentOrderInterface;

    public function __construct(
        Request $request,
        Logger $logger,
        LoggerHelper $loggerhelper,
        Response $response
    ) {
        $this->request      = $request;
        $this->logger       = $logger;
        $this->loggerhelper = $loggerhelper;
        $this->response     = $response;

    }

    /**
     * getlogger 設定 log 位置
     *
     * @param  mixed $path
     * @return mixed
     */
    public function getlogger($logger, $path = self::LOG_PATH)
    {
        return $this->loggerhelper->setPath($logger, $path);
    }

    /**
     * checkParams 確認請求參數
     *
     * @param  array $spec
     * @param  array $postData
     * @return array
     */
    public function checkParams($spec, $postData)
    {
        foreach ($spec as $key) {
            if (! isset($postData[$key])) {
                throw new \InvalidArgumentException("$key must be provisded");
            }
        }

    }
    
    /**
     * setResponse
     *
     * @param  array $array
     * @return 
     */
    public function setResponse($array)
    {
        $this->response->setHeader('Content-Type', 'application/json', true)
            ->setBody(json_encode($array))
            ->sendResponse();
    }
    
    /**
     * getInvoiceType
     *
     * @param  string|int $customerTaxId
     * @return int
     */
    public function getInvoiceType($customerTaxId)
    {
        return is_null($customerTaxId) ? 2 : 3;
    }
    
    /**
     * checkPkValue
     *
     * @param  string $needle
     * @param  array $collectedArray
     * @return bool
     */
    public function checkPkValue($needle, $collectedArray)
    {
        return in_array($needle, $collectedArray);
    }
    
    /**
     * filterHotaiV1Data
     *
     * @param  mixed $collection
     * @param  string $column
     * @return mixed
     */
    public function filterHotaiV1Data($collection, $column)
    {
        $collection->addFieldToFilter(
            $column,
            ['nlike' => '%hotai_order%'],
        );

        return $collection;
    }

    /**
     * replaceUnusedLabel
     *
     * @param  string $string
     * @return string
     */
    public function replaceUnusedLabel($string)
    {
        $unUsedLabel = ['hotai', '女士', '先生'];
        return str_replace($unUsedLabel, "", $string);
    }

    /**
     * filterHotaiHopesSeller
     * 過濾 Hotai Hopes 賣家
     * - HTC01: 所有訂單
     * - H0001: 僅超商取貨訂單
     *
     * @param  mixed $collection
     * @param  string $column - seller_code 欄位名稱
     * @return mixed
     */
    public function filterHotaiHopesSeller($collection, $column)
    {
        $convenienceStoreMethod = \Branch8\Shipping\Model\ShippingMethod::METHOD_CONVENIENCE_STORE;

        // 條件：若H0001 限制超商取貨
        $where = sprintf(
            "(%s = 'HTC01') OR (%s = 'H0001' AND sales_order.shipping_method = '%s')",
            $column,
            $column,
            $convenienceStoreMethod
        );
        $collection->getSelect()->where($where);

        return $collection;
    }

}
