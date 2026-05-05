<?php

namespace Branch8\MarketPlaceOrderExport\Model;

interface RecordsProviderInterface
{
    /**
     * @return mixed
     */
    public function getColumns();

    /**
     * @param array $columns
     * @return mixed
     */
    public function setColumns(array $columns);

    /**
     * @return []
     */
    public function getHeader();

    /**
     * @param array $header
     * @return mixed
     */
    public function setHeader(array $header);

    /**
     * @return []
     */
    public function getColumnSettings();

}
