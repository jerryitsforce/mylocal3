<?php
declare(strict_types=1);

namespace Branch8\Edenred\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Branch8\Edenred\Helper\Common as EdenredCommonHelper;
use Branch8\Edenred\Helper\Api as EdenredApiHelper;
use Branch8\Edenred\Model\Config\Source\LogOption as EdenredLogOption;

class CheckOrderItemQuantityNotExceedLimit implements \Magento\Framework\Event\ObserverInterface
{
    const LOG_FOLDER = 'Checkout/Observer/before_handle_master_quote';
    const LOG_OPTION_VALUE = EdenredLogOption::LOG_OPTION_VALUE_CHECKOUT_QTY_VALIDATOR;

    protected ScopeConfigInterface  $scopeConfig;
    protected ResourceConnection    $resourceConnection;
    protected EdenredCommonHelper   $edenredCommonHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resourceConnection,
        EdenredCommonHelper $edenredCommonHelper
    ) {
        $this->scopeConfig         = $scopeConfig;
        $this->resourceConnection  = $resourceConnection;
        $this->edenredCommonHelper = $edenredCommonHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->isValidatorSettingEnabled()) {
            return;
        }

        $masterQuote = $observer->getData('quote');

        foreach ($masterQuote->getAllVisibleItems() as $quoteItem) {
            if (!$this->edenredCommonHelper->IsEdenredTicketProduct((int) $quoteItem->getProductId())) {
                continue;
            }

            if ((int) $quoteItem->getQty() > EdenredApiHelper::QUANTITY_LIMIT) {
                $this->edenredCommonHelper->writeLogIfEnabled(
                    json_encode([
                        'exceptionMessage' => 'The quantity of each Edenred ticket product cannot exceed the limit per order.',
                        'limit'            => EdenredApiHelper::QUANTITY_LIMIT,
                        'quote'            => $masterQuote->getData(),
                        'quoteItem'        => $quoteItem,
                        'customerId'       => $masterQuote->getCustomerId(),
                    ]),
                    self::LOG_FOLDER,
                    self::LOG_OPTION_VALUE,
                );

                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The quantity of Edenred ticket product "%1" cannot exceed %2 per order.', $quoteItem->getName(), EdenredApiHelper::QUANTITY_LIMIT)
                );
            }
        }
    }

    protected function isValidatorSettingEnabled(): bool
    {
        return $this->scopeConfig->getValue(
            EdenredCommonHelper::CONFIG_PATH_QTY_VALIDATOR_ENABLE
        ) == 1;
    }
}