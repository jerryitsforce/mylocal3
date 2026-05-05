<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Model;

use Magento\Framework\Model\AbstractModel;
use Branch8\ProductCertification\Model\ResourceModel\CertificationType as ResourceModel;

/**
 * CertificationType Model
 */
class CertificationType extends AbstractModel
{
    public const STATUS_ENABLED  = 1;
    public const STATUS_DISABLED = 0;

    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * Get decoded category IDs
     * @return array
     */
    public function getCategoryIds(): array
    {
        $raw = $this->getData('category_ids');
        if (!$raw) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Set category IDs (encodes to JSON)
     * @param array $ids
     * @return $this
     */
    public function setCategoryIdsArray(array $ids): self
    {
        $this->setData('category_ids', json_encode(array_values($ids)));
        return $this;
    }

    /**
     * Check if active
     * @return bool
     */
    public function isActive(): bool
    {
        return (int)$this->getData('status') === self::STATUS_ENABLED;
    }
}
