<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Controller\Cart;

use C4B\FreeProduct\SalesRule\Action\GiftAction;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Tax\Helper\Data as TaxHelper;

class CheckPriceChange implements ActionInterface, HttpGetActionInterface
{
    /**
     * @var TaxHelper
     */
    private TaxHelper $taxData;

    /**
     * @var QuoteResource
     */
    private QuoteResource $quoteResource;

    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @var ResultJsonFactory
     */
    private ResultJsonFactory $resultJsonFactory;

    /**
     * CheckPriceChange constructor.
     *
     * @param TaxHelper $taxData
     * @param QuoteResource $quoteResource
     * @param CheckoutSession $checkoutSession
     * @param ResultJsonFactory $resultJsonFactory
     */
    public function __construct(
        TaxHelper         $taxData,
        QuoteResource     $quoteResource,
        CheckoutSession   $checkoutSession,
        ResultJsonFactory $resultJsonFactory
    ) {
        $this->taxData = $taxData;
        $this->quoteResource = $quoteResource;
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            if ($quote->getData('is_price_changed')) {
                $quote->setData('is_price_changed', 0);
                $this->quoteResource->save($quote);
                return $this->resultJsonFactory->create()->setData(true);
            } else {
                $isChanged = false;
                $isInclTax = $this->taxData->priceIncludesTax();
                $connection = $this->quoteResource->getConnection();
                $quoteItemTable = $connection->getTableName('quote_item');
                foreach ($quote->getAllVisibleItems() as $quoteItem) {
                    if ($quoteItem->getHasError()) {
                        continue;
                    }
                    $qty = $quoteItem->getQty();
                    $finalPrice = (float)$quoteItem->getProduct()->getFinalPrice();
                    $rowTotal = $isInclTax ? (float)$quoteItem->getPriceInclTax() : (float)$quoteItem->getPrice();
                    //ignnore check if it is a gift
                    if($quoteItem->getOptionByCode(GiftAction::ITEM_OPTION_UNIQUE_ID) instanceof \Magento\Quote\Model\Quote\Item\Option){
                        continue;
                    }
                    if (round($finalPrice) != round($rowTotal)) {
                        if ((bool)$quoteItem->getData('available_to_checkout') === false) {
                            if ($isInclTax) {
                                $bind = [
                                    'price_incl_tax' => $finalPrice,
                                    'base_price_incl_tax' => $finalPrice,
                                    'row_total_incl_tax' => round($finalPrice * $qty),
                                    'base_row_total_incl_tax' => round($finalPrice * $qty)
                                ];
                            } else {
                                $bind = [
                                    'price' => $finalPrice,
                                    'base_price' => $finalPrice,
                                    'row_total' => round($finalPrice * $qty),
                                    'base_row_total' => round($finalPrice * $qty)
                                ];
                            }
                            $connection->update($quoteItemTable, $bind, ['item_id=?' => $quoteItem->getItemId()]);
                        }else {
                            if (!$quote->getData('trigger_recollect')) {
                                $quote->setData('trigger_recollect', 1);
                                $this->quoteResource->save($quote);
                            }
                        }
                        if ($isChanged === false) {
                            $isChanged = true;
                        }
                    }
                }
                return $this->resultJsonFactory->create()->setData($isChanged);
            }
        } catch (\Exception $e) {
        }
        return $this->resultJsonFactory->create()->setData(false);
    }
}
