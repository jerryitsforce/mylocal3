<?php

namespace Branch8\Prelaunch\Controller\Index;

use Magento\Framework\App\Action\Context;

class Check extends \Magento\Framework\App\Action\Action
{

    protected $helperApi;

    public function __construct(
        \Branch8\Prelaunch\Helper\Api $helperApi,
        Context $context
    ){
        parent::__construct($context);
        $this->helperApi = $helperApi;
    }

    public function execute()
    {
        $item = $this->getRequest()->getParam('item');
        $dataApi = [
            'orderId' => null,
            'orderNo' => null,
            'syncProductList' => [
                [
                    'qty' => 0,
                    'productItemId' => (int)$item
                ]
            ],
            'isCancel' => false
        ];
        $result = $this->helperApi->stockAPI($dataApi);

    }


}