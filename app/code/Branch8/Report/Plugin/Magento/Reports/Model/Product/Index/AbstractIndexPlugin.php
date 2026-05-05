<?php

namespace Branch8\Report\Plugin\Magento\Reports\Model\Product\Index;

use Magento\Framework\Registry;

class AbstractIndexPlugin
{
    private Registry $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(
        Registry $registry
    )
    {
        $this->registry = $registry;
    }

    /**
     * @param $subject
     * @param ...$args
     * @return array
     */
    public function beforeCalculate($subject, ...$args)
    {
        return $args;
    }
}
