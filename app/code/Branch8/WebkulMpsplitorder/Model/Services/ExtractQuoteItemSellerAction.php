<?php
declare(strict_types=1);

namespace Branch8\WebkulMpsplitorder\Model\Services;

use Branch8\WebkulMpsplitorder\Model\TransferMasterQuoteItemDataToSubQuoteItemDataService;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;

class ExtractQuoteItemSellerAction
{
    private \Magento\Framework\ObjectManagerInterface $_objectManager;
    private \Webkul\Marketplace\Helper\Data $mpDataHelper;

    protected \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable;

    private array $modifiers;

    private LoggerInterface $logger;

    private TransferMasterQuoteItemDataToSubQuoteItemDataService $transferMasterQuoteItemDataToSubQuoteItemDataService;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Webkul\Marketplace\Helper\Data $mpDataHelper
     * @param \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable
     * @param TransferMasterQuoteItemDataToSubQuoteItemDataService $transferMasterQuoteItemDataToSubQuoteItemDataService
     * @param LoggerInterface $logger
     * @param array $modifiers
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface                    $objectManager,
        \Webkul\Marketplace\Helper\Data                              $mpDataHelper,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable,
        TransferMasterQuoteItemDataToSubQuoteItemDataService         $transferMasterQuoteItemDataToSubQuoteItemDataService,
        LoggerInterface                                              $logger,
        array                                                        $modifiers = []
    )
    {
        $this->mpDataHelper = $mpDataHelper;
        $this->_objectManager = $objectManager;
        $this->configurable = $configurable;
        $this->modifiers = $modifiers;
        $this->logger = $logger;
        $this->transferMasterQuoteItemDataToSubQuoteItemDataService = $transferMasterQuoteItemDataToSubQuoteItemDataService;
    }

    /**
     * @param Quote $quote
     * @return array
     */
    public function extract(Quote $quote)
    {
        $finalArray = [];
        $itemArray = [];
        $splitShip = 0;
        $typeCount = [];
        foreach ($quote->getAllItems() as $item) {
            $request = [];
            $shippable = 0;
            if ($item->getParentItem()) {
                continue;
            } else {
                if (!in_array($item->getProductType(), ["virtual", "downloadable"])) {
                    $shippable = $item->getQty();
                    $splitShip += $item->getQty();
                }
                foreach ($item->getOptions() as $option) {
                    if ($option->getCode() == "info_buyRequest") {
                        $value = json_decode($option->getValue(), true);
                        $value['qty'] = $item->getQty();
                        $request[] = $value;
                    }
                }
            }
            $id = 0;
            $rowTotal = $item->getRowTotal();
            //Check Assign Seller
            if (isset($request[0]['mpassignproduct_id']) && $request[0]['mpassignproduct_id'] != "") {
                $sellerId = $this->_objectManager->create(
                    \Webkul\MpAssignProduct\Helper\Data::class
                )->getAssignSellerIdByAssignId($request[0]['mpassignproduct_id']);
                $id = $sellerId;
                if (isset($request[0]['associate_id']) && $request[0]['associate_id']) {
                    $price = $this->_objectManager->create(
                        \Webkul\MpAssignProduct\Helper\Data::class
                    )->getAssocitePrice($request[0]['mpassignproduct_id'], $request[0]['associate_id']);
                } else {
                    $price = $this->_objectManager->create(
                        \Webkul\MpAssignProduct\Helper\Data::class
                    )->getAssignProductPrice($request[0]['mpassignproduct_id']);
                }
                $request[0]['price'] = $price;
                $rowTotal = $price;
            }
            if (!$id && !isset($request[0]['product'])) {
                $product = $this->configurable->getParentIdsByChild($item->getProductId());
                if (isset($product[0])) {
                    $proId = $product[0];
                } else {
                    $proId = $item->getProductId();
                }
            } else {
                $proId = $request[0]['product'];
            }
            $id = (int)$this->mpDataHelper->getSellerIdByProductId($proId);
            // todo: 改成$finalArray[$id][$type][], $type為要拆分訂單需要判斷的商品類型eg. 溫層, 虛擬商品
            // $type等於商品類型eg. 商品溫層(冷凍, 冷藏), 虛擬商品, 特殊, 其他則歸類為一般商品
            $type = $item->getIsVirtual() ? 'virtual' :
                ($item->getProduct()->getPreservationStatus() ?:
                    ($item->getProduct()->getIsSpecial() ? 'special' :
                        'normal'
                    )
                );

            $typeCount[$type] = $type;
            //var_dump($type);
            $newItemData = [
                'product' => $item->getProductId(),
                'request' => $request[0],
                'qty' => $item->getQty(),
                'item_id' => $item->getId(),
                'tax_amount' => $item->getTaxAmount(),
                'base_tax_amount' => $item->getBaseTaxAmount(),
                'discount' => $item->getDiscountAmount(),
                'base_discount' => $item->getBaseDiscountAmount(),
                'discount_percent' => $item->getDiscountPercent(),
                'shippable' => $shippable,
                'price' => $item->getPrice(),
            ];
            $extraFieldData = $this->transferMasterQuoteItemDataToSubQuoteItemDataService->transfer($item, []);
            $newItemData['extra_field_data'] = $extraFieldData;
            $finalArray[$id][$type][] = $newItemData;
            $itemArray[] = [
                'id' => $item->getId(),
                'row_total' => $rowTotal,
                'product_id' => $item->getProductId(),
                'tax_amount' => $item->getTaxAmount(),
                'discount_percent' => $item->getDiscountPercent(),
                'discount_amount' => $item->getDiscountAmount(),
                'base_discount_amount' => $item->getBaseDiscountAmount(),
                'seller_id' => $id
            ];
        }
        $input = new DataObject([
            'item_array' => $itemArray,
            'final_array' => $finalArray,
            'split_ship' => $splitShip,
            'type_count' => $typeCount
        ]);
        if ($this->modifiers) {
            /**
             * @var $modifier ExtractQuoteItemSellerActionModifierInterface
             */
            foreach ($this->modifiers as $modifier) {
                $modifier->modifier($input);
            }
        }
        return [
            $input->getData('final_array'),
            $input->getData('split_ship'),
            $input->getData('item_array'),
            $input->getData('type_count')
        ];
    }
}
