<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Branch8\ProductCertification\Model\CertificationType;

/**
 * Status option source
 */
class Status implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => CertificationType::STATUS_ENABLED,  'label' => __('Enabled')],
            ['value' => CertificationType::STATUS_DISABLED, 'label' => __('Disabled')],
        ];
    }
}
