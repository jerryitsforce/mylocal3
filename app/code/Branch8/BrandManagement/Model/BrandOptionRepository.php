<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\BrandManagement\Model;

use Branch8\BrandManagement\Api\BrandOptionRepositoryInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Brand Option Repository
 */
class BrandOptionRepository implements BrandOptionRepositoryInterface
{
    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var AttributeOptionManagementInterface
     */
    private $attributeOptionManagement;

    /**
     * @var AttributeOptionInterfaceFactory
     */
    private $optionFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param AttributeOptionManagementInterface $attributeOptionManagement
     * @param AttributeOptionInterfaceFactory $optionFactory
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductAttributeRepositoryInterface $attributeRepository,
        AttributeOptionManagementInterface $attributeOptionManagement,
        AttributeOptionInterfaceFactory $optionFactory,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->attributeOptionManagement = $attributeOptionManagement;
        $this->optionFactory = $optionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     *
     * Fetch all brand attribute options (store_id=0) for management UIs.
     */
    public function getBrandOptions()
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()
                ->from(['eaov' => 'eav_attribute_option_value'], ['option_id' => 'eaov.option_id', 'value' => 'eaov.value'])
                ->join(['eao' => 'eav_attribute_option'], 'eao.option_id = eaov.option_id', ['sort_order' => 'eao.sort_order'])
                ->join(['ea' => 'eav_attribute'], 'ea.attribute_id = eao.attribute_id', [])
                ->where('ea.attribute_code = ?', 'brand')
                ->where('ea.entity_type_id = ?', 4)
                ->where('eaov.store_id = ?', 0)
                ->order('eao.sort_order ASC');

            return $connection->fetchAll($select);
        } catch (\Exception $e) {
            $this->logger->error('Error getting brand options: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @inheritdoc
     *
     * Fetch a single brand attribute option (store_id=0) by option_id.
     */
    public function getBrandOptionById($optionId)
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()
                ->from(['eaov' => 'eav_attribute_option_value'], ['option_id' => 'eaov.option_id', 'value' => 'eaov.value'])
                ->join(['eao' => 'eav_attribute_option'], 'eao.option_id = eaov.option_id', ['sort_order' => 'eao.sort_order'])
                ->join(['ea' => 'eav_attribute'], 'ea.attribute_id = eao.attribute_id', [])
                ->where('ea.attribute_code = ?', 'brand')
                ->where('ea.entity_type_id = ?', 4)
                ->where('eaov.store_id = ?', 0)
                ->where('eaov.option_id = ?', $optionId);

            $result = $connection->fetchRow($select);
            return $result ?: null;
        } catch (\Exception $e) {
            $this->logger->error('Error getting brand option by ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @inheritdoc
     *
     * Create a new brand attribute option and ensure base/store values are persisted.
     */
    public function createBrandOption($brandName, $sortOrder = 0)
    {
        try {
            // Get the brand attribute with proper error handling
            try {
                $attribute = $this->attributeRepository->get( 'brand');
            } catch (\Exception $e) {
                throw new LocalizedException(__('Brand attribute does not exist. Please create the brand attribute first in Catalog > Attributes > Product Attributes.'));
            }
            
            if (!$attribute || !$attribute->getAttributeId()) {
                throw new LocalizedException(__('Brand attribute not found or not properly configured.'));
            }
            
            // Create option using direct database insert
            $connection = $this->resourceConnection->getConnection();
            
            // Insert into eav_attribute_option
            $connection->insert('eav_attribute_option', [
                'attribute_id' => $attribute->getAttributeId(),
                'sort_order' => $sortOrder
            ]);
            
            $optionId = $connection->fetchOne('SELECT LAST_INSERT_ID()');
            
            // Insert into eav_attribute_option_value
            $connection->insert('eav_attribute_option_value', [
                'option_id' => $optionId,
                'store_id' => 0,
                'value' => $brandName
            ]);

            // Insert into eav_attribute_option_value for store_id = 1
            $connection->insert('eav_attribute_option_value', [
                'option_id' => $optionId,
                'store_id' => 1,
                'value' => $brandName
            ]);

            return (int)$optionId;
        } catch (\Exception $e) {
            $this->logger->error('Error creating brand option: ' . $e->getMessage());
            throw new LocalizedException(__('Error creating brand option: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     *
     * Update an existing brand attribute option value and sort order.
     */
    public function updateBrandOption($optionId, $brandName, $sortOrder = 0)
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            
            // Update sort order in eav_attribute_option
            $connection->update(
                'eav_attribute_option',
                ['sort_order' => $sortOrder],
                ['option_id = ?' => $optionId]
            );

            // Update value in eav_attribute_option_value
            $connection->update(
                'eav_attribute_option_value',
                ['value' => $brandName],
                ['option_id = ?' => $optionId, 'store_id = ?' => 0]
            );

            // Update or insert value in eav_attribute_option_value for store_id = 1
            $existingStore1 = $connection->fetchOne(
                $connection->select()
                    ->from('eav_attribute_option_value')
                    ->where('option_id = ?', $optionId)
                    ->where('store_id = ?', 1)
            );
            
            if ($existingStore1) {
                // Update existing store 1 value
                $connection->update(
                    'eav_attribute_option_value',
                    ['value' => $brandName],
                    ['option_id = ?' => $optionId, 'store_id = ?' => 1]
                );
            } else {
                // Insert new store 1 value if it doesn't exist
                $connection->insert('eav_attribute_option_value', [
                    'option_id' => $optionId,
                    'store_id' => 1,
                    'value' => $brandName
                ]);
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error updating brand option: ' . $e->getMessage());
            throw new LocalizedException(__('Error updating brand option: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     *
     * Delete a brand attribute option by option_id.
     */
    public function deleteBrandOption($optionId)
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            
            // Delete from eav_attribute_option (this will cascade delete eav_attribute_option_value)
            $connection->delete(
                'eav_attribute_option',
                ['option_id = ?' => $optionId]
            );

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error deleting brand option: ' . $e->getMessage());
            throw new LocalizedException(__('Error deleting brand option: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     *
     * Check whether the specified brand option is referenced by any products.
     */
    public function isBrandOptionUsed($optionId)
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()
                ->from(['cpe' => 'catalog_product_entity'], ['count' => 'COUNT(*)'])
                ->join(['cpev' => 'catalog_product_entity_int'], 'cpev.attribute_id = cpe.entity_id', [])
                ->join(['ea' => 'eav_attribute'], 'ea.attribute_id = cpev.attribute_id', [])
                ->where('ea.attribute_code = ?', 'brand')
                ->where('cpev.value = ?', $optionId);

            $count = $connection->fetchOne($select);
            return (int)$count > 0;
        } catch (\Exception $e) {
            $this->logger->error('Error checking if brand option is used: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update extended brand option metadata in the custom `brand_option_extended` table.
     *
     * @param int $optionId
     * @param array $data
     * @return bool
     * @throws LocalizedException
     */
    public function updateBrandOptionExtended($optionId, $data)
    {
        try {
            // Update basic brand option
            $this->updateBrandOption($optionId, $data['value'], $data['sort_order']);
            
            $connection = $this->resourceConnection->getConnection();
            
            $extendedData = [
                'option_id' => $optionId,
                'is_featured' => $data['is_featured'] ?? 0,
                'show_in_brand_list' => $data['show_in_brand_list'] ?? 1,
                'show_in_brand_slider' => $data['show_in_brand_slider'] ?? 1,
                'position_in_slider' => $data['position_in_slider'] ?? 0,
                'english_alphabet' => $data['english_alphabet'] ?? '',
                'chinese_alphabet' => $data['chinese_alphabet'] ?? '',
                'url_alias' => $data['url_alias'] ?? ''
            ];
            
            // Check if extended data record exists
            $existingRecord = $connection->fetchRow(
                $connection->select()
                    ->from('brand_option_extended')
                    ->where('option_id = ?', $optionId)
            );
            
            if ($existingRecord) {
                // Update existing record
                $connection->update('brand_option_extended', $extendedData, ['option_id = ?' => $optionId]);
            } else {
                // Insert new record
                $extendedData['created_at'] = date('Y-m-d H:i:s');
                $extendedData['updated_at'] = date('Y-m-d H:i:s');
                $connection->insert('brand_option_extended', $extendedData);
            }
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Error updating brand option extended data: ' . $e->getMessage());
            throw new LocalizedException(__('Error updating brand option extended data: %1', $e->getMessage()));
        }
    }
}