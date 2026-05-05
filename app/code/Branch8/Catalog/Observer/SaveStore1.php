<?php

namespace Branch8\Catalog\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;

class SaveStore1 implements ObserverInterface{

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    protected $appState;
    /**
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        \Magento\Framework\App\State $appState
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->appState = $appState;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $storeId = $product->getStoreId();
        if($storeId == 1){
            $scriptName = isset($_SERVER['SCRIPT_NAME']) ?? '';
            $uri = isset($_SERVER['REQUEST_URI']) ?? '';
            $areaCode = $this->appState->getAreaCode();
            $backTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

            $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/product_store1.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info(print_r([
                'URI' => $uri,
                'Script_Name' => $scriptName,
                'areaCode' => $areaCode,
                'trace' => $backTrace
            ], true));
        }


    }
}
