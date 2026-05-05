<?php

namespace Branch8\Mmegamenu\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Branch8\Mmegamenu\Helper\Generator;
use Magento\Framework\App\Action\HttpGetActionInterface;

class MenuData extends Action implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Generator
     */
    protected $generatorHelper;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Generator $generatorHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Generator $generatorHelper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->generatorHelper = $generatorHelper;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();

        try {
            $menuData = $this->generatorHelper->getMenuCacheData();

            return $result->setData([
                'success' => true,
                'data' => $menuData
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
