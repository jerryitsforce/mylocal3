<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Plugin\Webkul\OptionsWithStockAndImages\Plugin;

class ReorderPlugin
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $salesOrderFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Logger\Logger
     */
    protected $logger;

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Webkul\OptionsWithStockAndImages\Helper\Data $helper
     * @param \Magento\Sales\Model\OrderFactory $salesOrderFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param \Webkul\OptionsWithStockAndImages\Logger\Logger $logger
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Webkul\OptionsWithStockAndImages\Helper\Data $helper,
        \Magento\Sales\Model\OrderFactory $salesOrderFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Webkul\OptionsWithStockAndImages\Logger\Logger $logger
    ) {
        $this->request = $request;
        $this->helper = $helper;
        $this->salesOrderFactory = $salesOrderFactory;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->logger = $logger;
    }

    public function aroundAroundExecute(
        \Webkul\OptionsWithStockAndImages\Plugin\ReorderPlugin $subject,
        \Closure $proceed
    ) {
        try {
            /** work start here */
            if ($orderId = $this->request->getParam('order_id')) {
                $salesOrderModel = $this->salesOrderFactory->create();
                $salesOrderModel->load($orderId);

                if (count($salesOrderModel->getAllItems()) > 0) {
                    foreach ($salesOrderModel->getAllItems() as $quoteItem) {
                        $combArr = [];
                        $productId = $quoteItem->getProductId();
                        $productRowId = $quoteItem->getProduct()->getRowId();
                        $optionData = $this->helper->getOptionData($productId);
                        $itemOptionsOrderQtyArr = $this->getItemOptions($quoteItem->getProductOptions());
                        $itemOptions = $itemOptionsOrderQtyArr['itemOptions'];
                        $orderedQty = $itemOptionsOrderQtyArr['orderedQty'];
                        $combArr = $this->getCombinationsData(
                            $itemOptions,
                            $orderedQty,
                            $optionData,
                            $combArr
                        );
                        $isVariation = $this->isVariationAvailable(
                            $itemOptions,
                            $optionData,
                            $productRowId,
                            $combArr
                        );
                        if ($isVariation) {
                            throw new \Magento\Framework\Exception\StateException(
                                __('Requested quantity not available for requested combination.')
                            );
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            return $this->resultRedirectFactory->create()->setPath('sales/order/history');
        }
        return $proceed();
    }

    /**
     * Get Item Options
     *
     * @param array $productOptions
     * @return array
     */
    public function getItemOptions($productOptions)
    {
        $itemOptions = [];
        $orderedQty = 0;
        foreach ($productOptions as $optionKey => $option) {
            if ($optionKey=="info_buyRequest") {
                $orderedQty = $option['qty'];
                $itemOptions = isset($option['options'])?$option['options']:[];
                break;
            }
        }
        return [
            'orderedQty' =>$orderedQty,
            'itemOptions' =>$itemOptions
        ];
    }

    /**
     * Is Variation Available
     *
     * @param array $itemOptions
     * @param array $optionData
     * @param int $productId
     * @param array $combArr
     * @return boolean
     */
    public function isVariationAvailable($itemOptions, $optionData, $productId, $combArr)
    {
        $comb = "";
        if (empty($itemOptions)) {
            return false;
        }
        foreach ($itemOptions as $key => $value) {
            if (isset($optionData[$key]) && isset($optionData[$key][$value])) {
                $comb .= $optionData[$key][$value]."_";
            }
        }
        $comb = trim($comb, "_");
        $variation = $this->helper->getCombData($productId, $comb);
        if ($variation->getId() && ($variation->getStock()-$combArr[$comb])<0) {
            return true;
        }
        return false;
    }

    /**
     * Get Combinations Data
     *
     * @param array $itemOptions
     * @param float|int $orderedQty
     * @param array $optionData
     * @param array $combArr
     * @return array
     */
    private function getCombinationsData($itemOptions, $orderedQty, $optionData, $combArr)
    {
        try {
            if (!empty($itemOptions)) {
                $comb = "";
                foreach ($itemOptions as $key => $value) {
                    if (isset($optionData[$key]) && isset($optionData[$key][$value])) {
                        $comb .= $optionData[$key][$value]."_";
                    }
                }
                $comb = trim($comb, "_");
                if ($comb) {
                    if (isset($combArr[$comb])) {
                        $combArr[$comb] += $orderedQty;
                    } else {
                        $combArr[$comb] = $orderedQty;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
        return $combArr;
    }
}