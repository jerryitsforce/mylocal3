<?php

namespace Branch8\TicketApi\Helper\NotifyHandler;

use Branch8\TicketApi\Model\Api\Data\NotifyData as Data;
use Branch8\TicketApi\Model\Api\Data\NotifyDataFactory as DataFactory;

class BaseHandler
{
    /** @var DataFactory */
    protected $dataFactory;

    /** @var Data */
    protected $returnData;

    protected $returnCode;
    protected $returnMsg;

    public function __construct(
        DataFactory $dataFactory
    ) {
        $this->dataFactory = $dataFactory;
        $this->returnData  = $this->dataFactory->create();
    }

    /**
     * @return null|string
     */
    public function getReturnCode(): null|string
    {
        return $this->returnCode;
    }

    /**
     * @return null|string
     */
    public function getReturnMsg(): null|string
    {
        return $this->returnMsg;
    }

    /**
     * @return Data
     */
    public function getReturnData(): Data
    {
        return $this->returnData;
    }
}