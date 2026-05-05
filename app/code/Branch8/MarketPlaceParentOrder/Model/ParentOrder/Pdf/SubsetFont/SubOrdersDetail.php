<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       05/03/2026
 */

namespace Branch8\MarketPlaceParentOrder\Model\ParentOrder\Pdf\SubsetFont;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Branch8\MarketPlaceParentOrder\Model\Services\SubOrderFinder;
use Branch8\MaskInformation\Api\MaskRulesCompositeInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\RtlTextHandler;

class SubOrdersDetail implements TextExtractorInterface
{
    private SubOrderFinder $subOrderFinder;

    protected $resourceConnection;
    private RtlTextHandler $rltlTextHandler;
    private $rtlTextHandler;

    /**
     * @param ResourceConnection $resourceConnection
     * @param SubOrderFinder $subOrderFinder
     * @param RtlTextHandler $rtlTextHandler
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        SubOrderFinder     $subOrderFinder,
        RtlTextHandler     $rtlTextHandler
    )
    {
        $this->rltlTextHandler = $rtlTextHandler;
        $this->resourceConnection = $resourceConnection;
        $this->subOrderFinder = $subOrderFinder;
        $this->rtlTextHandler = $rtlTextHandler;
    }

    /**
     * @param ParentOrder $parentOrder
     * @return array
     */
    public function extract(ParentOrder $parentOrder)
    {
        $text = [];
        $sellerOrders = $this->subOrderFinder->find($parentOrder);
        $shopTitles = [];
        if ($sellerOrders) {
            $sellerIds = array_keys($sellerOrders);
            $shopTitles = $this->getShopTitle($sellerIds);
        }
        foreach ($sellerOrders as $sellerId => $sellerOrderInfo) {
            /**
             * @var $seller \Magento\Customer\Model\Data\Customer
             */
            $seller = $sellerOrderInfo['seller'];
            $orders = $sellerOrderInfo['orders'];
            foreach ($orders as $key => $order) {
                if ($order->getData('is_flagship_store_process_order')) {
                    continue;
                }
                if (isset($shopTitles[$seller->getId()])) {
                    $shopTitle = $shopTitles[$seller->getId()];
                } else {
                    $shopTitle = $seller->getFirstname() . ' ' . $seller->getLastname();
                }
                $incrementId = str_replace('hotai_order_', '', $order->getIncrementId());
                $text = array_merge($text, [$shopTitle, $incrementId]);
                /**
                 * @var $item \Magento\Sales\Model\Order\Item
                 */
                foreach ($order->getAllItems() as $item) {
                    if ($item->getParentItem()) {
                        continue;
                    }
                    /* Draw item */
                    $text[] = $this->rtlTextHandler->reverseRtlText(html_entity_decode($item->getName()));
                    if ($item instanceof \Magento\Sales\Model\Order\Item) {
                        $text[] = $item->getSku();
                    } else if ($item->getOrderItem()->getProductOptionByCode('simple_sku')) {
                        $text[] = $item->getOrderItem()->getProductOptionByCode('simple_sku');
                    }
                    $options = $this->getItemOptions($item);
                    if ($this->getItemOptions($item)) {
                        foreach ($options as $option) {
                            $text[] = $option['value'];
                        }
                    }
                }
            }
        }
        return array_merge($text, [
            __('Item Specification')->render(),
            __('Unit Price')->render(),
            __('Quantity')->render(),
            __('合計')->render(),
            __('Freight')->render(),
            __('Small Plan')->render()
        ]);
    }

    /**
     * @param $item
     * @return array
     */
    private function getItemOptions($item)
    {
        $result = [];
        /**
         *
         */
        if ($item instanceof \Magento\Sales\Model\Order\Item) {
            $options = $item->getProductOptions();
        } else {
            $options = $item->getOrderItem()->getProductOptions();
        }
        if ($options) {
            if (isset($options['options'])) {
                $result[] = $options['options'];
            }
            if (isset($options['additional_options'])) {
                $result[] = $options['additional_options'];
            }
            if (isset($options['attributes_info'])) {
                $result[] = $options['attributes_info'];
            }
        }
        return array_merge([], ...$result);
    }

    /**
     * @param $sellerIds
     * @return array
     */
    private function getShopTitle($sellerIds)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from('marketplace_userdata',
            ['seller_id', 'company_name'])->where('seller_id IN (?)', $sellerIds);
        return $connection->fetchPairs($select);
    }
}
