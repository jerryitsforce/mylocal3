<?php

namespace Branch8\Prelaunch\Plugin;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

class ValidateStockOldSystemCustomOption
{
    protected $prelaunchHelperData;

    protected $productCollectionFactory;

    protected $prelaunchHelperApi;

    protected $productRepository;
    private LoggerInterface $logger;

    /**
     * @param \Branch8\Prelaunch\Helper\Data $prelaunchHelperData
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Branch8\Prelaunch\Helper\Api $prelaunchHelperApi
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Branch8\Prelaunch\Helper\Data                                 $prelaunchHelperData,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Branch8\Prelaunch\Helper\Api                                  $prelaunchHelperApi,
        ProductRepositoryInterface                                     $productRepository,
        LoggerInterface                                                $logger
    )
    {
        $this->prelaunchHelperData = $prelaunchHelperData;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->prelaunchHelperApi = $prelaunchHelperApi;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    public function afterBackStockQty($subject, $result, $sku, $qtyOrdered, $order, $message = '')
    {
        if (!$this->prelaunchHelperData->isActive()) {
            return $result;
        }
        if (!$this->prelaunchHelperData->validateLimitSku($sku)) {
            return $result;
        }
        $product = $this->productRepository->get($sku);
        $productExtAttribute = $product->getExtensionAttributes();
        $isManageStock = $productExtAttribute->getStockItem()->getManageStock();
        if (!$isManageStock) {
            return $result;
        }

        $itemId = $this->prelaunchHelperData->parseItemId($sku);
        $productList[] = [
            'qty' => (int)$qtyOrdered,
            'productItemId' => $itemId
        ];
        $requestData = [
            'orderId' => $order->getIncrementId(),
            'orderNo' => $order->getIncrementId(),
            'syncProductList' => $productList,
            'isCancel' => true
        ];
        $response = $this->prelaunchHelperApi->stockAPI($requestData);
        if ($response['success'] != 1) {
            return $result;
        }
        try {
            $responseParsed = json_decode($response['response'], true);
            if (!$responseParsed || !$responseParsed['isSuccess']) {
                return $result;
            }
            $dataList = $responseParsed['dataList'];
            foreach ($dataList as $_item) {
                if (!$_item['isSuccess']) {
                    continue;
                }
                $remainQty = $_item['remainingQty'];
                $result = $remainQty;
                $balanceQty = (int)$qtyOrdered;
                /**
                 * This is special case, at this place Magento qty added but salable qty not added
                 * So need to balance by qty ordered
                 */
                $this->prelaunchHelperData->setProductQty($sku, $remainQty, $balanceQty);
            }
            return $result;
        } catch (\Exception $e) {
            return $result;
        }
        return $result;
    }

    public function afterSubtractStockQty($subject, $result, $sku, $qtyOrdered, $order, $message = '')
    {
        if (!$this->prelaunchHelperData->isActive()) {
            return $result;
        }
        if (!$this->prelaunchHelperData->validateLimitSku($sku)) {
            return $result;
        }
        $product = $this->productRepository->get($sku);
        $productExtAttribute = $product->getExtensionAttributes();
        $isManageStock = $productExtAttribute->getStockItem()->getManageStock();
        if (!$isManageStock) {
            return $result;
        }

        $itemId = $this->prelaunchHelperData->parseItemId($sku);
        $productList[] = [
            'qty' => (int)$qtyOrdered,
            'productItemId' => $itemId
        ];
        $requestData = [
            'orderId' => $order->getIncrementId(),
            'orderNo' => $order->getIncrementId(),
            'syncProductList' => $productList,
            'isCancel' => false
        ];
        $response = $this->prelaunchHelperApi->stockAPI($requestData);
        if ($response['success'] != 1) {
            return $result;
        }
        try {
            $responseParsed = json_decode($response['response'], true);
            if (!$responseParsed || !$responseParsed['isSuccess']) {
                return $result;
            }
            $dataList = $responseParsed['dataList'];
            foreach ($dataList as $_item) {
                if (!$_item['isSuccess']) {
                    continue;
                }
                $remainQty = $_item['remainingQty'];
                $result = $remainQty;
                $this->prelaunchHelperData->setProductQty($sku, $remainQty);
            }
            return $result;
        } catch (\Exception $e) {
            return $result;
        }
        return $result;
    }

    /**
     * @param \Branch8\OptionsWithStockAndImages\Helper\Salable $subject
     * @param callable $process
     * @param $requestQty
     * @param $productRowId
     * @param $comb
     * @return bool
     */
    public function aroundCheckStockAvailabilityForCombo(
        \Branch8\OptionsWithStockAndImages\Helper\Salable $subject,
        callable                                          $process,
                                                          $requestQty,
                                                          $productRowId,
                                                          $comb
    )
    {
        /**
         * @var $variation \Webkul\OptionsWithStockAndImages\Model\Variations
         */
        if (!$this->prelaunchHelperData->isActive()) {
            return $process($requestQty, $productRowId, $comb);
        }
        $variation = $subject->webkulHelper->getCombData($productRowId, $comb);
        if (!$variation->getId() || !$variation->getIsSync()) {
            return $process($requestQty, $productRowId, $comb);
        }
        $sku = $variation->getSku();
        if (!$this->prelaunchHelperData->validateLimitSku($sku)) {
            return $process($requestQty, $productRowId, $comb);
        }
        $itemId = $this->prelaunchHelperData->parseItemId($sku);
        $productList[] = [
            'qty' => 0,
            'productItemId' => $itemId
        ];
        $requestData = [
            'orderId' => null,
            'orderNo' => null,
            'syncProductList' => $productList,
            'isCancel' => true
        ];
        try {
            $response = $this->prelaunchHelperApi->stockAPI($requestData);
            if ($response['success'] != 1) {
                return false;
            }
            $responseParsed = json_decode($response['response'], true);
            if (!$responseParsed['isSuccess']) {
                return false;
            }
            $dataList = $responseParsed['dataList'];
            foreach ($dataList as $_item) {
                if (!$_item['isSuccess']) {
                    continue;
                }
                $remainQty = $_item['remainingQty'];
                $this->prelaunchHelperData->setProductQty($sku, $remainQty);
                if ($requestQty > $remainQty) {
                    return false;
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->logger->info('RequestData' . print_r($requestData, true));
            $this->logger->info($e->getTraceAsString());
            return false;
        }
        return true;
    }

    public function aroundCheckAvailability($subject, $process, $itemOptions, $productCombArr, $optionData, $comb, $productId, $item)
    {
        $notAvailableArr = [];

        if (!$this->prelaunchHelperData->isActive()) {
            return $process($itemOptions, $productCombArr, $optionData, $comb, $productId, $item);
        }

        $options = json_decode($itemOptions);

        if (isset($options->options)) {
            foreach ($options->options as $key => $value) {
                if (isset($optionData[$key]) && isset($optionData[$key][$value])) {
                    $comb .= $optionData[$key][$value] . "_";
                }
            }

            $comb = trim($comb, "_");
            $variation = $subject->webkulHelper->getCombData($productId, $comb);

            if ($variation->getIsSync()) {
                $sku = $variation->getSku();

                if (!$this->prelaunchHelperData->validateLimitSku($sku)) {
                    return $process($itemOptions, $productCombArr, $optionData, $comb, $productId, $item);
                }

                $itemId = $this->prelaunchHelperData->parseItemId($sku);
                $productList[] = [
                    'qty' => 0,
                    'productItemId' => $itemId
                ];
                $requestData = [
                    'orderId' => null,
                    'orderNo' => null,
                    'syncProductList' => $productList,
                    'isCancel' => true
                ];

                $response = $this->prelaunchHelperApi->stockAPI($requestData);
                if ($response['success'] != 1) {
                    $notAvailableArr[] = $item->getName() . " (" . $comb . ")";
                    return $notAvailableArr;
                }
                try {
                    $responseParsed = json_decode($response['response'], true);
                    if (!$responseParsed['isSuccess']) {
                        $notAvailableArr[] = $item->getName() . " (" . $comb . ")";
                        return $notAvailableArr;
                    }
                    $dataList = $responseParsed['dataList'];
                    foreach ($dataList as $_item) {
                        if (!$_item['isSuccess']) {
                            continue;
                        }
                        $remainQty = $_item['remainingQty'];
                        $this->prelaunchHelperData->setProductQty($sku, $remainQty);
                        if ($item->getQty() > $remainQty) {
                            $notAvailableArr[] = $item->getName() . " (" . $comb . ")";
                            return $notAvailableArr;
                        }
                    }
                    return $notAvailableArr;
                } catch (\Exception $e) {
                    $notAvailableArr[] = $item->getName() . " (" . $comb . ")";
                    return $notAvailableArr;
                }

            }
        }
        return $process($itemOptions, $productCombArr, $optionData, $comb, $productId, $item);
    }
}
