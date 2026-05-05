<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Branch8\BrandManagement\Model\Request;

use Amasty\ShopbyBase\Model\OptionSetting as OptionSettingModel;

class BrandRegistry
{
    /**
     * @var OptionSettingModel|null
     */
    private ?OptionSettingModel $brandOption = null;

    /**
     * Get the current brand option setting model stored in the registry.
     *
     * @return OptionSettingModel|null
     */
    public function get(): ?OptionSettingModel
    {
        return $this->brandOption;
    }

    /**
     * Store the current brand option setting model in the registry.
     *
     * @param OptionSettingModel|null $brandOption Option setting for the brand being edited.
     * @return void
     */
    public function set(?OptionSettingModel $brandOption): void
    {
        $this->brandOption = $brandOption;
    }
}

