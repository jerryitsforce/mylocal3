<?php

namespace Branch8\SplitCart\Override\Plugin\Magento\Quote\Model;

use Magento\Framework\Registry;
use Tigren\SplitCart\Helper\Data;

class Quote extends \Tigren\SplitCart\Plugin\Magento\Quote\Model\Quote{

    private $registry;

    public function __construct(
        Registry $registry,
        Data $data
    ){
        parent::__construct($registry, $data);
        $this->registry = $this->registry;
    }

    public function afterHasItems(
        \Magento\Quote\Model\Quote $subject,
                                   $result
    ) {
        return $result;
    }

}