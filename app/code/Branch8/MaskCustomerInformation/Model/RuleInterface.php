<?php

namespace Branch8\MaskCustomerInformation\Model;

use Magento\Framework\DataObject;

interface RuleInterface
{
    /**
     * @param array $data
     * @return array
     */
    public function apply(array $data): array;

    /**
     * @return array
     */
    public function getRuleConfig(): array;
}
