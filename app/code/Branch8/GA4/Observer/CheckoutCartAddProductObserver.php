<?php

namespace Branch8\GA4\Observer;

use Branch8\GA4\Model\Config;
use Branch8\GA4\Model\ProductHelper;
use Magento\Framework\Event\ObserverInterface;

class CheckoutCartAddProductObserver implements ObserverInterface
{
    /**
     * @var Config
     */
    private $helper;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $_objectManager;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;
    /**
     * @var \Branch8\GA4\Model\Datalayer
     */
    private $datalayer;
    /**
     * @var ProductHelper
     */
    private $productHelper;

    /**
     * @param \Branch8\GA4\Model\Datalayer $datalayer
     * @param Config $helper
     * @param ProductHelper $productHelper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Checkout\Model\Session $_checkoutSession
     */
    public function __construct(
        \Branch8\GA4\Model\Datalayer              $datalayer,
        Config                                    $helper,
        ProductHelper                             $productHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Checkout\Model\Session           $_checkoutSession
    )
    {
        $this->productHelper = $productHelper;
        $this->datalayer = $datalayer;
        $this->helper = $helper;
        $this->_objectManager = $objectManager;
        $this->_checkoutSession = $_checkoutSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helper->isEnabled()) {
            return $this;
        }

        $product = $observer->getData('product');
        $request = $observer->getData('request');

        $params = $request->getParams();

        if (isset($params['qty']) && (int)$params['qty'] > 0) {
            $filter = new \Magento\Framework\Filter\LocalizedToNormalized(
                ['locale' => $this->_objectManager->get('Magento\Framework\Locale\ResolverInterface')->getLocale()]
            );
            $qty = $filter->filter($params['qty']);
        } else {
            $qty = 1;
        }

        if ($product->getTypeId() == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE) {
            $superGroup = $params['super_group'];
            $superGroup = is_array($superGroup) ? array_filter($superGroup, 'intval') : [];
            $associatedProducts = $product->getTypeInstance()->getAssociatedProducts($product);
            foreach ($associatedProducts as $associatedProduct) {
                if (isset($superGroup[$associatedProduct->getId()]) && ($superGroup[$associatedProduct->getId()] > 0)) {
                    $currentAddToCartData = $this->_checkoutSession->getGA4AddToCartData();
                    $addToCartPushData = $this->productHelper->addToCartPushData(
                        $superGroup[$associatedProduct->getId()], $associatedProduct
                    );
                    $newAddToCartPushData = $this->productHelper->mergeAddToCartPushData(
                        $currentAddToCartData, $addToCartPushData
                    );

                    $newAddToCartPushData = $this->addAdditionData($newAddToCartPushData, $request);
                    $this->_checkoutSession->setGA4AddToCartData(null);
                    $this->_checkoutSession->unsGA4AddToCartData();
                    $this->_checkoutSession->setGA4AddToCartData($newAddToCartPushData);

                }
            }
        } else {
            $addToCartPushData = $this->productHelper->addToCartPushData($qty, $product);
            $addToCartPushData = $this->addAdditionData($addToCartPushData, $request);
            $this->_checkoutSession->setGA4AddToCartData(null);
            $this->_checkoutSession->unsGA4AddToCartData();
            $this->_checkoutSession->setGA4AddToCartData(
                $addToCartPushData
            );
        }

        return $this;
    }

    public function addAdditionData($pushData, $request)
    {
        $params = $request->getParams();
        foreach ($pushData['items'] ?? [] as $key => $item) {
            if(isset($params['item_list_id'])) {
                $pushData['items'][$key]['item_list_id'] = $params['item_list_id'] ?? '';
            }

            if(isset($params['item_list_name'])) {
                $pushData['items'][$key]['item_list_name'] = $params['item_list_name'] ?? '';
            }

            if(isset($params['promotion_id'])) {
                $pushData['items'][$key]['promotion_id'] = $params['promotion_id'] ?? '';
            }

            if(isset($params['promotion_name'])) {
                $pushData['items'][$key]['promotion_name'] = $params['promotion_name'] ?? '';
            }
        }

        return $pushData;
    }
}
