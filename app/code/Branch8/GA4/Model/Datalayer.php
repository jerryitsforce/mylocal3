<?php
/**
 * @package
 * @author      Cuong Ho <cuonghh@forixwebdesign.com>
 * @copyright   Copyright © 2021 Forix LLC. All Rights Reserved. *
 */
declare(strict_types=1);

namespace Branch8\GA4\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManager;

class Datalayer
{

    private $request;

    private $checkoutSession;

    private $blockFactory;

    private $storage;

    private $orderRepository;

    private $storeManager;

    private \Magento\Framework\Registry $registry;
    private \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency;

    /**
     * @param \Magento\Framework\View\Element\BlockFactory $blockFactory
     * @param RequestInterface $request
     * @param Storage $storage
     * @param OrderRepositoryInterface $orderRepository
     * @param Session $checkoutSession
     * @param StoreManager $storeManager
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\View\Element\BlockFactory      $blockFactory,
        RequestInterface                                  $request,
        Storage                                           $storage,
        OrderRepositoryInterface                          $orderRepository,
        Session                                           $checkoutSession,
        StoreManager                                      $storeManager,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Registry                       $registry
    )
    {
        $this->registry = $registry;
        $this->blockFactory = $blockFactory;
        $this->checkoutSession = $checkoutSession;
        $this->request = $request;
        $this->storage = $storage;
        $this->orderRepository = $orderRepository;
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
    }

    private function createBlock($blockName, $template)
    {
        if ($block = $this->blockFactory->createBlock('\\Branch8\\GA4\\Block\\' . $blockName)
            ->setTemplate('Branch8_GA4::' . $template)
        ) {
            return $block;
        }

        return false;
    }

    public function getDataLayerScript()
    {
        $script = '';

        if (!($block = $this->createBlock('Core', 'datalayer.phtml'))) {
            return $script;
        }

        $block->setNameInLayout('branch8.ga4.datalayer.scripts');

        /*  $this->addDefaultInformation();
          $this->addCategoryPageInformation();
          $this->addSearchResultPageInformation();
          $this->addProductPageInformation();
          $this->addCartPageInformation();*/
        // $this->addProductPageInformation();
         $this->addCartPageInformation();
         $this->addCheckoutFailInfo();
        $this->addProductPageInformation();
        $this->addCheckoutInformation();
        $this->addOrderInformation();
        $html = $block->toHtml();

        return $html;
    }

    public function addCartPageInformation()
    {
        $requestPath = $this->request->getModuleName() .
            DIRECTORY_SEPARATOR . $this->request->getControllerName() .
            DIRECTORY_SEPARATOR . $this->request->getActionName();

        if ($requestPath == 'checkout/cart/index') {
            $cartBlock = $this->createBlock('Cart', 'cart.phtml');

            if ($cartBlock) {
                $quote = $this->checkoutSession->getQuote();
                $cartBlock->setQuote($quote);
                $cartBlock->toHtml();
            }
        }
    }

    public function addCheckoutFailInfo()
    {
        $requestPath = $this->request->getModuleName() .
            DIRECTORY_SEPARATOR . $this->request->getControllerName() .
            DIRECTORY_SEPARATOR . $this->request->getActionName();

        if ($requestPath == 'checkout/onepage/failure') {
            $cartBlock = $this->createBlock('Fail', 'fail.phtml');

            if ($cartBlock) {
                $quote = $this->checkoutSession->getQuote();
                $cartBlock->setQuote($quote);
                $cartBlock->toHtml();
            }
        }
    }

    /**
     * @return void
     */
    public function addProductPageInformation()
    {
        $currentProduct = $this->getCurrentProduct();

        if (!empty($currentProduct)) {
            $productBlock = $this->createBlock('Product', 'product.phtml');
            if ($productBlock) {
                $productBlock->setCurrentProduct($currentProduct);
                $productBlock->toHtml();
            }
        }
    }

    /**
     * @return mixed
     */
    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }

    /**
     * @return void
     */
    public function addOrderInformation()
    {
        $lastOrderId = $this->checkoutSession->getLastOrderId();
        $requestPath = $this->request->getModuleName() .
            DIRECTORY_SEPARATOR . $this->request->getControllerName() .
            DIRECTORY_SEPARATOR . $this->request->getActionName();

        $requestPathDynamic = $this->request->getModuleName() .
            DIRECTORY_SEPARATOR . $this->request->getControllerName() .
            DIRECTORY_SEPARATOR . '*';

        $successPagePaths = [
            'checkout/onepage/success'
        ];

        if (!$lastOrderId) {
            return;
        }

        if (!in_array($requestPath, $successPagePaths) && !in_array($requestPathDynamic, $successPagePaths)) {
            return;
        }

        $orderBlock = $this->createBlock('Order', 'order.phtml');
        if ($orderBlock) {
            $order = $this->orderRepository->get($lastOrderId);
            $orderBlock->setOrder($order);
            $orderBlock->toHtml();
        }


    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function addCheckoutInformation()
    {
        $requestPath = $this->request->getModuleName() .
            DIRECTORY_SEPARATOR . $this->request->getControllerName() .
            DIRECTORY_SEPARATOR . $this->request->getActionName();

        if ($requestPath == 'checkout/index/index') {
            $checkoutBlock = $this->createBlock('Checkout', 'checkout.phtml');

            if ($checkoutBlock) {
                $quote = $this->checkoutSession->getQuote();
                $checkoutBlock->setQuote($quote);
                $checkoutBlock->toHtml();
            }
        }
    }

    /**
     * @param $step
     * @param $checkoutOption
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addCheckoutStepPushData($step, $checkoutOption)
    {
        $checkoutStepResult = [];
        $products = [];
        $total = 0;
        $checkoutBlock = $this->createBlock('Checkout', 'checkout.phtml');
        if ($checkoutBlock) {
            $quote = $this->checkoutSession->getQuote();
            $checkoutBlock->setQuote($quote);
            $products = $checkoutBlock->getProducts();
            $total = $checkoutBlock->getGa4Total();
        }

        switch ($step) {
            case '1':
                $eventName = 'add_shipping_info';
                $optionName = 'shipping_tier';
                break;
            case '2':
                $eventName = 'add_payment_info';
                $optionName = 'payment_type';
                if (empty($products)) {
                    $lastOrderId = $this->checkoutSession->getLastOrderId();
                    $orderBlock = $this->createBlock('Order', 'order.phtml');
                    if ($orderBlock && $lastOrderId) {
                        $order = $this->orderRepository->get($lastOrderId);
                        $orderBlock->setOrder($order);
                        $products = $orderBlock->getProducts();
                        $total = $orderBlock->getGa4Total();
                    }
                }
                break;
            default:
                $eventName = '';
                $optionName = '';
        }

        $checkoutStepResult['event'] = $eventName;
        $checkoutStepResult['value'] = $total;
        $checkoutStepResult['currency'] = $this->getCurrencyCode();
        $checkoutStepResult['ecommerce'] = [];
        $checkoutStepResult['ecommerce']['items'] = [];
        $checkoutStepResult['ecommerce']['items'] = $products;
        $checkoutStepResult['ecommerce'][$optionName] = $checkoutOption;

        $result = [];
        $result[] = $checkoutStepResult;

        return $result;
    }

    public function formatMoney($value)
    {
        return number_format((float)$value, 2, '.', '');
    }

    public function getCartTotal()
    {
        $quote = $this->checkoutSession->getQuote();
        $grandTotal = $quote->getGrandTotal();
        if (!$grandTotal) {
            $lastOrderId = $this->checkoutSession->getLastOrderId();
            if ($lastOrderId) {
                $order = $this->orderRepository->get($lastOrderId);
                $grandTotal = $order->getGrandTotal();
            }
        }

        return $grandTotal;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrencyCode()
    {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * @param $price
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function convertPriceToCurrentCurrency($price)
    {
        if ($this->getCurrencyCode() != $this->getBaseCurrencyCode()) {
            return $this->priceCurrency->convert($price, $this->storeManager->getStore(), $this->getCurrencyCode());
        }
        return $price;
    }

    /**
     * @return string
     */
    public function getBaseCurrencyCode()
    {
        return $this->storeManager->getStore()->getBaseCurrencyCode();
    }

}
