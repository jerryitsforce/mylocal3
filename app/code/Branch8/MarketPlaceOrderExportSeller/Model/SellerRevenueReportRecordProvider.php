<?php

namespace Branch8\MarketPlaceOrderExportSeller\Model;

use Branch8\MarketPlaceOrderExport\Model\RecordsProviderInterface;

class SellerRevenueReportRecordProvider implements RecordsProviderInterface
{
    /**
     * @var array
     */
    private $columns = [];
    /**
     * @var array
     */
    private $columSettings;
    /**
     * @var array
     */
    private $headers = [];

    /**
     * @param array $columns
     * @param array $columSettings
     */
    public function __construct(
        array $columns = [],
        array $columSettings = [],
    )
    {
        $this->columns = $columns;
        $this->columSettings = $columSettings;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     * @return $this|mixed
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;
        return $this;
    }


    public function getHeader()
    {
        return $this->headers;
    }

    /**
     * @param array $header
     * @return $this|mixed
     */
    public function setHeader(array $header)
    {
        $this->headers = $header;
        return $this;
    }

    /**
     * @return array
     */
    public function getColumnSettings()
    {
        return $this->columSettings;
    }

}
