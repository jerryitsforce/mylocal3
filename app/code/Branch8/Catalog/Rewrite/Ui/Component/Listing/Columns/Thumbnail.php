<?php

declare(strict_types=1);

namespace Branch8\Catalog\Rewrite\Ui\Component\Listing\Columns;

class Thumbnail extends \Magento\Catalog\Ui\Component\Listing\Columns\Thumbnail
{
    /**
     * @inheritdoc
     */
    protected function getAlt($row): ?string
    {
        $altField = $this->getData('config/altField') ?: self::ALT_FIELD;
        // phpcs:disable Magento2.Functions.DiscouragedFunction
        return isset($row[$altField]) ? html_entity_decode($row[$altField], ENT_QUOTES, "UTF-8") : null;
    }
}
