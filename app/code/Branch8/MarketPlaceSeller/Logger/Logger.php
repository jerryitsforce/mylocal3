<?php

namespace Branch8\MarketPlaceSeller\Logger;

use Magento\Framework\ObjectManagerInterface;

class Logger extends \Branch8\HotaiCore\Logger\LoggerProxy
{
    /**
     * @param ObjectManagerInterface $objectManager
     * @param string|null $moduleName
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ?string $moduleName = null,
    ) {
        $moduleName ??= 'Branch8_MarketPlaceSeller';
        parent::__construct($objectManager, $moduleName);
    }
}
