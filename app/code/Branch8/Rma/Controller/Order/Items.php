<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Rma\Controller\Order;

use Branch8\HotaiCore\Helper\VirtualProduct;
use Branch8\MaskInformation\Model\MaskRulesComposite;
use Branch8\Shipping\Model\ShippingMethod;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceCollection;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollection;
use Branch8\Customer\Helper\Info;

class Items extends \Webkul\MpRmaSystem\Controller\Order\Items implements HttpPostActionInterface
{
    /**
     * @var InvoiceCollection
     */
    protected $invoiceCollection;

    /**
     * @var ShipmentCollection
     */
    protected $shipmentCollection;

    /**
     * @var  \Branch8\Rma\Helper\Data
     */
    protected $mpRmaHelper;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $order;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * @var \Webkul\Marketplace\Model\OrdersFactory
     */
    protected $mpOrder;

    /**
     * @var MaskRulesComposite
     */
    protected MaskRulesComposite $maskRulesComposite;

    /**  @var \Branch8\HotaiCore\Helper\VirtualProduct $virtualProduct */
    protected $virtualProduct;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $priceHelper;

    /**
     * @var Info
     */
    protected $infoHelper;

    public function __construct(
        Context $context,
        \Branch8\Rma\Helper\Data $mpRmaHelper,
        \Magento\Sales\Model\OrderFactory $order,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Webkul\Marketplace\Model\OrdersFactory $mpOrder,
        InvoiceCollection $invoiceCollection,
        ShipmentCollection $shipmentCollection,
        MaskRulesComposite $maskRulesComposite,
        VirtualProduct $virtualProduct,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        Info $infoHelper
    ) {
        $this->maskRulesComposite = $maskRulesComposite;
        $this->virtualProduct = $virtualProduct;  
        $this->priceHelper = $priceHelper;
        $this->infoHelper = $infoHelper;
        parent::__construct($context, $mpRmaHelper, $order, $resultJsonFactory, $imageHelper, $mpOrder, $invoiceCollection, $shipmentCollection);
    }
    /**
     * Rma Items Action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $data = $this->_request->getParams();
        $info = [];
        $orderDetails = [];
        $sellers = [];

        $info['isLoggedIn'] = 1;
        $info['items'] = [];

        if (array_key_exists("is_guest", $data) && !$this->isBuyerLoggedIn($data['is_guest'])) {
            $info['isLoggedIn'] = 0;
        }

        //Get Order
        $orderId = $this->_request->getParam('order_id');
        $order = $this->order->create()->load($orderId);

        //Get Item
        $itemId = $this->_request->getParam('item_id');
        $item = $this->mpRmaHelper->getOrderItem($orderId, $itemId);
        $product = $item->getProduct();

        //Setup Seller info
        $details = $this->mpRmaHelper->getSellerDetailsByProductId($product->getId());
        $sellers[$details['seller_id']] = $details['seller_name'];

        $orderDetails[$details['seller_id']][] = $item->getId();
        $orderDetails = $this->getStatusDetails($orderDetails);
        $orderDetails = $this->setOrderDetailsAddressInfo($order, $orderDetails);

        $info = $this->setMultiSeller($orderDetails, $info);

        //Setup OrderItemInfo
        $orderStatus = $orderDetails[$details['seller_id']]['order_status'] ?? $order->getStatus();
        $qty = $this->mpRmaHelper->getRmaQty(
            $item->getId(),
            $order->getId(),
            $item->getQtyOrdered(),
            $orderStatus
        );

        $options = $item->getProductOptions();

        $arr = [
            'is_virtual' => (int) $item->getProduct()->getIsVirtual(),
            'ticket_info' => $this->getTicketInfo($item),
            'product_url' => $product->getProductUrl(),
            'product_image' => $this->getProductImageUrl($product),
            'price' => $order->formatPrice(($item->getData('row_total_incl_tax')) - $item->getData('row_total_point_used')),
            'sku' => $item->getSku(),
            'name' => $item->getName(),
            'qty' => $qty,
            'original_qty' => $item->getQtyOrdered(),
            'id' => $product->getId(),
            'item_id' => $item->getId(),
            'itemId' => $item->getId(),
            'point_used' => !empty($item->getData('row_total_point_used')) ? $this->priceHelper->currency($item->getData('row_total_point_used'), false, false) : 0,
            'productUrl' => $product->getProductUrl(),
            'productImage' => $this->getProductImageUrl($product),
            "optionHtml" => $this->getOptionsHtml($item),
            "optionText" => $this->getOptionsText($item),
            "point_money_config_type" => $options['PointMoneyConfigType'] ?? null
        ];

        $info["items"][] = $arr;
        $info["sellers"] = $sellers;
        $info["order_details"] = $orderDetails;

        // Set return json info
        $result = $this->resultJsonFactory->create();
        $result->setData($info);
        return $result;
    }

    /**
     * setOrderDetailsAddressInfo
     *
     * @param  mixed $order
     * @param  array $orderDetails
     * @return array
     */
    private function setOrderDetailsAddressInfo($order, $orderDetails) {
        $shippingAddress = $order->getShippingAddress();
        $name = trim(str_replace('PreName', '', $order->getCustomerName()));
        $orderDetails['customer_name'] = $name;
        $orderDetails['customer_name_masked'] = $this->infoHelper->getOAuthName('', $name);
        $orderDetails['shipping_address_street'] = '';
        $orderDetails['shipping_address_city'] = '';
        $orderDetails['shipping_address_region'] = '';
        $orderDetails['class'] = get_class($order);
        $orderDetails['phone'] = '';
        $orderDetails['isTicketOrder'] = $this->isTicketOrder($order);
        $orderDetails['shipping_method'] = $order->getShippingMethod();
        $orderDetails['isConvenienceStore'] = $order->getShippingMethod() == ShippingMethod::METHOD_CONVENIENCE_STORE;

        $isExchange = $order->getShippingMethod() == ShippingMethod::METHOD_CONVENIENCE_STORE ? 1 : 0;

        $orderDetails['is_exchange'] = $isExchange;

        if ($shippingAddress) {
            if ($isExchange){
               $storeInfo = json_decode($shippingAddress->getData('store_address_info') ?? '', true);
               $orderDetails['store_name'] = $storeInfo['storename'] ?? '';
               $orderDetails['store_address'] = $storeInfo['address'] ?? '';
                /**
                 * I saw that in some case, the store_address_info is empty, but store name save to city, address to street
                 */
               if($orderDetails['store_name'] == ''){
                   $orderDetails['store_name'] = $shippingAddress->getData('city');
               }
               if($orderDetails['store_address'] == ''){
                   $orderDetails['store_address'] = $shippingAddress->getData('street');
               }

                $orderDetails['shipping_address_street'] = '';
                $orderDetails['shipping_address_city'] = $order->getShippingAddress()->getCity() ?? '';
                $orderDetails['shipping_address_region'] = $order->getShippingAddress()->getRegion() ?? '';
                $orderDetails['phone'] = $order->getShippingAddress()->getTelephone() ?? '';
                
                $orderDetails['shipping_address_street_masked'] = '';
                $orderDetails['phone_masked'] = $this->infoHelper->getOAuthPhone($orderDetails['phone']);
                
            } else {
                $orderDetails['shipping_address_street'] = $order->getShippingAddress()->getStreet() ?? '';
                if (is_array($orderDetails['shipping_address_street'])) {
                    $orderDetails['shipping_address_street'] = implode(' ', $orderDetails['shipping_address_street']);
                }
                $orderDetails['shipping_address_city'] = $order->getShippingAddress()->getCity() ?? '';
                $orderDetails['shipping_address_region'] = $order->getShippingAddress()->getRegion() ?? '';
                $orderDetails['phone'] = $order->getShippingAddress()->getTelephone() ?? '';

                $orderDetails['shipping_address_street_masked'] = $this->infoHelper->getOAuthStreet($orderDetails['shipping_address_street']);
                $orderDetails['phone_masked'] = $this->infoHelper->getOAuthPhone($orderDetails['phone']);
            }
        }

        return $orderDetails;

    }

    /**
     * setMultiSeller
     *
     * @param  array $orderDetails
     * @param  array $info
     * @return array
     */
    private function setMultiSeller($orderDetails, $info) {
        if (count($orderDetails) > 1) {
            $info['multi_seller'] = 1;
        } else {
            $info['multi_seller'] = 0;
            foreach ($orderDetails as $sellerId => $statusData) {
                $info['order_status'] = $statusData['order_status'];
                $info['shipment_status'] = $statusData['shipment_status'];
            }
        }

        return $info;
    }

    /**
     * getProductImageUrl
     *
     * @param  mixed $product
     * @return string
     */
    private function getProductImageUrl($product) {
        return $this->imageHelper
        ->init($product, 'product_page_image_small')
        ->setImageFile($product->getImage())
        ->keepAspectRatio(true)
        ->resize(100, 100)
        ->getUrl();
    }

    /**
     * getTicketInfo
     *
     * @param  mixed $item
     * @return array
     */
    private function getTicketInfo($item) {
        $ticketArray = [];
        $checkIsTicket = $this->virtualProduct->checkIsProductTicketTypeByOrderItemId((int) $item->getId());

        if (!$checkIsTicket){
            return $ticketArray;
        }

        $ticketArray['info'] = $this->virtualProduct->getTicketStatusByOrderItemId((int) $item->getId());
        return $ticketArray;
    }

    /**
     * Get all options as text
     *
     * @param $item
     * @return string
     */
    private function getOptionsText($item)
    {
        $optionText = '';
        $productOptions = $this->getItemOptions($item->getProductOptions());
        if (!empty($productOptions)) {
            foreach ($productOptions as $option) {
                $optionValue = !empty($option['print_value']) ? $option['print_value'] : $option['value'];
                $optionText .= ($optionText === '' ? '' : '+').$optionValue;
            }
            // $optionText = rtrim($optionText, ' +');
        }

        return $optionText;
    }

    /**
     * Get available options
     *
     * @param $options
     * @return array
     */
    private function getItemOptions($options)
    {
        $result = [];
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
     * Is ticket order
     * @param $order
     * @return bool
     */
    public function isTicketOrder($order)
    {
        $items = $order->getAllItems();
        $isVirtual = true;
        foreach ($items as $item) {
            if (!$item->getData('is_virtual')) {
                $isVirtual = false;
                return $isVirtual;
            }
        }
        return $isVirtual;
    }
}
