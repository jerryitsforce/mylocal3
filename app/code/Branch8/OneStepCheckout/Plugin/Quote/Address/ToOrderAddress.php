<?php

namespace Branch8\OneStepCheckout\Plugin\Quote\Address;

use Magento\Framework\App\ResourceConnection;

class ToOrderAddress
{

    protected $resource;

    /**
     * @var string
     */
    protected $connectionName;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    public function __construct(
        ResourceConnection $resource,
    )
    {
        $this->resource = $resource;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address\ToOrderAddress $subject
     * @param \Magento\Sales\Api\Data\OrderAddressInterface $orderAddress
     * @param \Magento\Quote\Model\Quote\Address $quoteAddress
     * @return \Magento\Sales\Api\Data\OrderAddressInterface
     */
    public function afterConvert(
        \Magento\Quote\Model\Quote\Address\ToOrderAddress $subject,
        \Magento\Sales\Api\Data\OrderAddressInterface $orderAddress,
        \Magento\Quote\Model\Quote\Address $quoteAddress
    ) {
        // Get the quote_id
        $quoteId = $quoteAddress->getQuoteId();

        // Fetch data from quote and quote_address using the quoteId
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['q' => $this->resource->getTableName('quote')])
            ->joinLeft(
                ['qa' => $this->resource->getTableName('quote_address')],
                'q.parent_id = qa.quote_id',
                ['store_address_info','cvs_store_code','cvs_store_name','cvs_store_servicetype','cvs_store_outside']
            )
            ->where('q.entity_id = ?', $quoteId);

        // Fetch the results
        $result = $connection->fetchAll($select);
        
        //set some custom value to $orderAddress:
        if (!empty($result)) {
            if (isset($result[0]['store_address_info'])) {
                $orderAddress->setData('store_address_info', $result[0]['store_address_info']);
            }
            if (isset($result[0]['cvs_store_code'])) {
                $orderAddress->setData('cvs_store_code', $result[0]['cvs_store_code']);
            }
            if (isset($result[0]['cvs_store_name'])) {
                $orderAddress->setData('cvs_store_name', $result[0]['cvs_store_name']);
            }
            if (isset($result[0]['cvs_store_servicetype'])) {
                $orderAddress->setData('cvs_store_servicetype', $result[0]['cvs_store_servicetype']);
            }
            if (isset($result[0]['cvs_store_outside'])) {
                $orderAddress->setData('cvs_store_outside', $result[0]['cvs_store_outside']);
            }
        }

        return $orderAddress;
    }

    /**
     * @return AdapterInterface|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected function getConnection()
    {
        if (!$this->connection) {
            $this->connection = $this->resource->getConnection($this->connectionName);
        }
        return $this->connection;
    }
}
