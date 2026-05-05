<?php

namespace Branch8\GA4\Controller\Cart;

use Branch8\GA4\Model\Config;
use Branch8\GA4\Model\ProductHelper;
use Magento\Checkout\Model\Cart\RequestQuantityProcessor;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote\Item;
use Psr\Log\LoggerInterface;
use Branch8\HotaiCore\Helper\VirtualProduct as VirtualProductHelper;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;

class UpdateItemQty extends \Magento\Checkout\Controller\Cart\UpdateItemQty
{

    private $quantityProcessor;

    /**
     * @var FormKeyValidator
     */
    private $formKeyValidator;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Config
     */
    private $helper;

    /**
     * @var \Branch8\GA4\Model\Datalayer
     */
    private $datalayer;
    /**
     * @var ProductHelper
     */
    private $productHelper;

    /**
     * @var VirtualProductHelper
     */
    private $virtualProductHelper;

    /**
     * @var HotaiCoreCommonHelper
     */
    private $hotaiCoreCommonHelper;

    public function __construct(
        Context $context,
        RequestQuantityProcessor $quantityProcessor,
        FormKeyValidator $formKeyValidator,
        CheckoutSession $checkoutSession,
        Json $json,
        LoggerInterface $logger,
        \Branch8\GA4\Model\Datalayer              $datalayer,
        Config                                    $helper,
        ProductHelper                             $productHelper,
        VirtualProductHelper                      $virtualProductHelper,
        HotaiCoreCommonHelper                     $hotaiCoreCommonHelper
    )
    {
        $this->quantityProcessor = $quantityProcessor;
        $this->formKeyValidator = $formKeyValidator;
        $this->checkoutSession = $checkoutSession;
        $this->json = $json;
        $this->logger = $logger;
        $this->productHelper = $productHelper;
        $this->datalayer = $datalayer;
        $this->helper = $helper;
        $this->virtualProductHelper = $virtualProductHelper;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        parent::__construct($context, $quantityProcessor, $formKeyValidator, $checkoutSession, $json, $logger);
    }


    public function execute()
    {
        try {
            $this->validateRequest();
            $this->validateFormKey();

            $cartData = $this->getRequest()->getParam('cart');

            $this->validateCartData($cartData);

            $cartData = $this->quantityProcessor->process($cartData);
            $quote = $this->checkoutSession->getQuote();
            $cartItems = $quote->getAllItems();
            $itemNeedToUpdate = [];
            foreach($cartItems as $_item){
                $cartItemQty = $_item->getQty();
                if(!isset($cartData[$_item->getId()])){
                    continue;
                }
                $cartItemPost = $cartData[$_item->getId()];
                if((int)$cartItemPost['qty'] != (int)$cartItemQty){
                    $itemNeedToUpdate[$_item->getId()] = $cartItemPost['qty'];
                }

            }

            $response = [];

            foreach ($itemNeedToUpdate as $itemId => $itemInfo) {
                $item = $quote->getItemById($itemId);
                $qty = $itemInfo; //isset($itemInfo['qty']) ? (double) $itemInfo['qty'] : 0;

                if ($this->virtualProductHelper->isBatchImportTicketProduct($item->getProductId())) {
                    $checkResultArray = $this->virtualProductHelper->checkIfQuantityEnoughByCustomOptionAndRequestQuantity(
                        $item->getProductId(),
                        $this->virtualProductHelper->getBatchSettingCustomOptionValueForQuoteItemFlow($item)->getTitle(),
                        $qty
                    );

                    $isQuantityEnough = $checkResultArray['result'] ?? false;
                    $reason = $checkResultArray['reason'] ?? null;

                    if (!$isQuantityEnough) {
                        $response[] = [
                            'error' => __("The requested quantity is not available. Please adjust the purchase quantity."),
                            'itemId' => $itemId
                        ];
                    }
                }

                if ($item) {
                    try {
                        $this->updateItemQuantity($item, $qty);
                    } catch (LocalizedException $e) {
                        $response[] = [
                            'error' => $e->getMessage(),
                            'itemId' => $itemId
                        ];
                    }
                }
            }

            // Fix: Save quote to persist changes
            try {
                $quote->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }

            $this->jsonResponse(count($response)? json_encode($response) : '');
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->jsonResponse('Something went wrong while saving the page. Please refresh the page and try again.');
        }
    }

    /**
     * Updates quote item quantity.
     *
     * @param Item $item
     * @param float $qty
     * @return void
     * @throws LocalizedException
     */
    private function updateItemQuantity(Item $item, float $qty)
    {
        $oldQty = $item->getQty();

        if ($qty > 0) {
            $item->clearMessage();
            $item->setHasError(false);
            $item->setQty($qty);

            if ($item->getHasError()) {
                throw new LocalizedException(__($item->getMessage()));
            }

            if ($qty > $oldQty) {
                $qtyDiff = $qty - $oldQty;

                $product = $item->getProduct();
                $addToCartPushData = $this->productHelper->addToCartPushData($qtyDiff, $product);
                $currentAddToCartData = $this->checkoutSession->getGA4AddToCartData();
                if ($currentAddToCartData) {
                    $addToCartPushData = $this->productHelper->mergeAddToCartPushData(
                        $currentAddToCartData,
                        $addToCartPushData
                    );
                }

                $this->checkoutSession->setGA4AddToCartData($addToCartPushData);
            }
        }
    }

    /**
     * JSON response builder.
     *
     * @param string $error
     * @return void
     */
    private function jsonResponse(string $error = '')
    {
        $this->getResponse()->representJson(
            $this->json->serialize($this->getResponseData($error))
        );
    }

    /**
     * Returns response data.
     *
     * @param string $error
     * @return array
     */
    private function getResponseData(string $error = ''): array
    {
        $response = ['success' => true];

        if (!empty($error)) {
            $response = [
                'success' => false,
                'error_message' => $error,
            ];
        }

        return $response;
    }

    /**
     * Validates the Request HTTP method
     *
     * @return void
     * @throws NotFoundException
     */
    private function validateRequest()
    {
        if ($this->getRequest()->isPost() === false) {
            throw new NotFoundException(__('Page Not Found'));
        }
    }

    /**
     * Validates form key
     *
     * @return void
     * @throws LocalizedException
     */
    private function validateFormKey()
    {
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            throw new LocalizedException(
                __('Something went wrong while saving the page. Please refresh the page and try again.')
            );
        }
    }

    /**
     * Validates cart data
     *
     * @param array|null $cartData
     * @return void
     * @throws LocalizedException
     */
    private function validateCartData($cartData = null)
    {
        if (!is_array($cartData)) {
            throw new LocalizedException(
                __('Something went wrong while saving the page. Please refresh the page and try again.')
            );
        }
    }


}