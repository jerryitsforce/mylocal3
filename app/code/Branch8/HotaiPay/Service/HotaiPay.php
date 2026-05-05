<?php 

namespace Branch8\HotaiPay\Service;

use Branch8\HotaiPay\Helper\Data;
use Branch8\HotaiPay\Service\Api;

class HotaiPay
{        
    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        public Data $data,
        public Api $api
    ){
        $this->data = $data;
        $this->api = $api;
    }
}
?>