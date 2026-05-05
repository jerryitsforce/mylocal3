<?php

declare(strict_types=1);

namespace Branch8\ProductCertification\Ui\DataProvider;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Branch8\ProductCertification\Model\ResourceModel\CertificationType\Collection;
use Branch8\ProductCertification\Model\ResourceModel\CertificationType\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Branch8\ProductCertification\Helper\ImageUploader;
use Magento\Store\Model\StoreManagerInterface;

/**
 * DataProvider for the Certification Type form
 */
class CertificationTypeDataProvider extends AbstractDataProvider
{
    /**
     * @var array
     */
    private array $loadedData = [];

    /**
     * @var DataPersistorInterface
     */
    private DataPersistorInterface $dataPersistor;

    /**
     * @var ImageUploader
     */
    private ImageUploader $imageUploader;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param ImageUploader $imageUploader
     * @param StoreManagerInterface $storeManager
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        ImageUploader $imageUploader,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection    = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->imageUploader = $imageUploader;
        $this->storeManager  = $storeManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        /** @var \Branch8\ProductCertification\Model\CertificationType $item */
        foreach ($items as $item) {
            $data = $item->getData();

            // Transform icon
            if (!empty($data['icon'])) {
                $data['icon'] = [[
                    'name' => basename($data['icon']),
                    'url'  => $this->imageUploader->getIconUrl($data['icon']),
                    'file' => $data['icon'],
                    'size' => 0,
                    'type' => 'image'
                ]];
            }

            // Transform category_ids (UI Select often expects strings for tree selection)
            $data['category_ids'] = array_map('strval', $item->getCategoryIds());

            // Index by ID and wrap in 'data' for form binding
            $id = $item->getId();
            $this->loadedData[$id] = $data;
        }

        $persistedData = $this->dataPersistor->get('branch8_certification_type');
        if (!empty($persistedData)) {
            // Transform icon for persisted data
            if (!empty($persistedData['icon'])) {
                $persistedData['icon'] = [[
                    'name' => basename($persistedData['icon']),
                    'url'  => $this->imageUploader->getIconUrl($persistedData['icon']),
                    'file' => $persistedData['icon'],
                    'size' => 0,
                    'type' => 'image'
                ]];
            } else {
                $persistedData['icon'] = [];
            }

            // Transform category_ids for persisted data
            if (isset($persistedData['category_ids']) && is_string($persistedData['category_ids'])) {
                $persistedData['category_ids'] = json_decode($persistedData['category_ids'], true);
            }
            $persistedData['category_ids'] = array_map('strval', (array)($persistedData['category_ids'] ?? []));

            $id = $persistedData['entity_id'] ?? 0;
            $this->loadedData[$id] = $persistedData;
            $this->dataPersistor->clear('branch8_certification_type');
        }

        return $this->loadedData;
    }
}
