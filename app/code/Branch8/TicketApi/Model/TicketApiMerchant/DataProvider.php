<?php

namespace Branch8\TicketApi\Model\TicketApiMerchant;

use Magento\Framework\App\Request\Http;
use Branch8\TicketApi\Model\ResourceModel\TicketApiMerchant\CollectionFactory;
use Branch8\TicketApi\Model\TicketApiMerchant;
use Branch8\TicketApi\Model\TicketApiMerchantRepository;
use Branch8\TicketApi\Model\TicketApiPermission;
use Branch8\TicketApi\Model\TicketApiPermissionRepository;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /** @var array */
    private $loadedData;

    /** @var Http */
    private $request;

    /** @var TicketApiMerchantRepository */
    private $ticketApiMerchantRepository;

    /** @var TicketApiPermissionRepository */
    private $ticketApiPermissionRepository;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        Http $request,
        CollectionFactory $collectionFactory,
        TicketApiMerchantRepository $ticketApiMerchantRepository,
        TicketApiPermissionRepository $ticketApiPermissionRepository,
        array $meta = [],
        array $data = []
    ) {
        $this->request                       = $request;
        $this->collection                    = $collectionFactory->create();
        $this->ticketApiMerchantRepository   = $ticketApiMerchantRepository;
        $this->ticketApiPermissionRepository = $ticketApiPermissionRepository;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $id = $this->request->getParam('entity_id');

        if (!$id) {
            return [];
        }

        $this->collection->addFieldToFilter(TicketApiMerchant::ENTITY_ID, $id);
        $merchant = $this->ticketApiMerchantRepository->getById($id);

        $this->loadedData[$id] = $merchant->getData();

        $this->loadedData[$id]["brand_permission"] = $this->getPermissionData($id);

        return $this->loadedData;
    }

    protected function getPermissionData(int $merchantId): array
    {
        $result = [];

        $collection = $this->ticketApiPermissionRepository->getPermissionByMerchantId($merchantId);

        /** @var TicketApiPermission $permission */
        foreach ($collection->getItems() as $permission) {
            $result[] = (string) $permission->getBrandId();
        }

        return $result;
    }
}