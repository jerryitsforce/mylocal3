<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       05/03/2026
 */

namespace Branch8\MarketPlaceParentOrder\Model\ParentOrder\Pdf\SubsetFont;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Branch8\MaskInformation\Api\MaskRulesCompositeInterface;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class SummarizeBlock implements TextExtractorInterface
{
    private ParentOrderManagementInterface $parentOrderManagement;
    /***
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;
    private MaskRulesCompositeInterface $maskRulesComposite;

    /**
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param ScopeConfigInterface $scopeConfig
     * @param MaskRulesCompositeInterface $maskRulesComposite
     */
    public function __construct(
        ParentOrderManagementInterface $parentOrderManagement,
        ScopeConfigInterface                 $scopeConfig,
        MaskRulesCompositeInterface    $maskRulesComposite
    )
    {
        $this->parentOrderManagement = $parentOrderManagement;
        $this->scopeConfig = $scopeConfig;
        $this->maskRulesComposite = $maskRulesComposite;
    }

    /**
     * @param ParentOrder $parentOrder
     * @return array
     */
    public function extract(ParentOrder $parentOrder)
    {
        $text = [];
        $paymentMethod = $parentOrder->getDetail()->getPaymentMethod();
        $paymentMethodTitle = $this->getMethodStoreTitle((string)$paymentMethod, (int)$parentOrder->getDetail()->getStoreId());
        $billingAddress = $parentOrder->getBillingAddress();
        if ($billingAddress) {
            $addressLine = implode('  /  ', [
                'name' => $this->maskRulesComposite->mask('name', $billingAddress->getName()),
                'phone' => $this->maskRulesComposite->mask('phone', $billingAddress->getTelephone()),
                'address' => $this->maskRulesComposite->mask('address', implode('\n', $billingAddress->getStreet()))
            ]);
            $text[] = $addressLine;
        }
        return array_merge($text, [
            '訂單主編號',
            __('商品總額')->render(),
            __('運費')->render(),
            __('元')->render(),
            __('點數折抵')->render(),
            __('點')->render(),
            __('實付⾦額')->render(),
            __('元')->render(),
            __('⽀付⽅式')->render(),
            $paymentMethodTitle,
            __('收件者/物流資訊')->render(),
            'NT',
            '$',
            __('Event Discount')->render()
        ]);
    }

    /**
     * @param string $code
     * @param int|null $storeId
     * @return string
     */
    private function getMethodStoreTitle(string $code, ?int $storeId = null): string
    {
        $configPath = sprintf('%s/%s/title', 'payment', $code);
        return (string)$this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
