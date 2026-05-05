<?php

declare(strict_types=1);

namespace Branch8\Catalog\Plugin\Ui\Component\Product;

use Branch8\Catalog\Helper\Config as ConfigHelper;
use Magento\Catalog\Ui\Component\Product\MassAction as BaseMassAction;

class MassAction
{
    /**
     * @var ConfigHelper
     */
    private ConfigHelper $configHelper;

    /**
     * Constructor.
     *
     * @param ConfigHelper $configHelper
     */
    public function __construct(ConfigHelper $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    /**
     * Checks that mass delete of products is not available.
     *
     * @param BaseMassAction $subject
     * @param bool $isAllowed
     * @param string $actionType
     *
     * @return bool
     */
    public function afterIsActionAllowed(BaseMassAction $subject, bool $isAllowed, string $actionType): bool
    {
        if ($actionType === 'delete' && $this->configHelper->isHideDeleteProductMassAction()) {
            return false;
        }

        return $isAllowed;
    }
}
