<?php

namespace Branch8\WidgetCache\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Provide selectable log files for WidgetCache module.
 */
class LogOption implements OptionSourceInterface
{
    /**
     * Return log file options for admin multiselect.
     *
     * @return array<int,array<string,string>>
     */
    public function toOptionArray(): array
    {
        return [
            $this->option('CacheClearCommand', 'Command_CacheClearCommand'),
            $this->option('CacheStatsCommand', 'Command_CacheStatsCommand'),
            $this->option('WidgetCache', 'Model_WidgetCache'),
            $this->option('Type', 'Model_Type'),
            $this->option('Data', 'Helper_Data'),
            $this->option('CacheStats', 'Block_CacheStats'),
            $this->option('ProductSaveAfter', 'Observer_ProductSaveAfter'),
            $this->option('ProductAttributeUpdate', 'Observer_ProductAttributeUpdate'),
            $this->option('ProductsListPlugin', 'Plugin_ProductsListPlugin'),
            $this->option('BlockPlugin', 'Plugin_BlockPlugin'),
            $this->option('BestSellerPlugin', 'Plugin_BestSellerPlugin'),
            $this->option('ProductPointPlugin', 'Plugin_ProductPointPlugin'),
            $this->option('BrandListPlugin', 'Plugin_BrandListPlugin'),
            $this->option('CategoryListPlugin', 'Plugin_CategoryListPlugin'),
            $this->option('StockItemRepositoryPlugin', 'Plugin_StockItemRepositoryPlugin'),
            $this->option('SourceItemsSavePlugin', 'Plugin_SourceItemsSavePlugin'),
        ];
    }

    /**
     * Build one option row.
     *
     * @param string $value Config value.
     * @param string $originalLabel Original label prefix.
     * @return array<string,string>
     */
    private function option(string $value, string $originalLabel): array
    {
        return [
            'value' => $value,
            'label' => __(
                '%1 (var/log/WidgetCache/%2/{Y_m_d}.log)',
                $originalLabel,
                $value
            ),
        ];
    }
}
