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
use Branch8\MaskInformation\Api\MaskRulesCompositeInterface;

class ParentOrderDetail implements TextExtractorInterface
{
    private MaskRulesCompositeInterface $maskRuleComposite;

    /**
     * @param MaskRulesCompositeInterface $maskRulesComposite
     */
    public function __construct(
        MaskRulesCompositeInterface $maskRulesComposite
    )
    {
        $this->maskRuleComposite = $maskRulesComposite;
    }

    /**
     * @param ParentOrder $parentOrder
     * @return array
     */
    public function extract(ParentOrder $parentOrder)
    {
        $twId = $this->getCustomerId($parentOrder);
        $name = $this->maskRuleComposite->mask('name',
            (string)$parentOrder->getDetail()->getCustomerName()
        );
        return [
            __('Proof Purchase Receipt')->render(),
            __('%1 (Member ID : %2)', $name, $twId)->render(),
            __('訂購完成⽇')->render(),
            __('Buyer')->render(),
        ];
    }

    /**
     * @param ParentOrder $parentOrder
     * @return string
     */
    private function getCustomerId(ParentOrder $parentOrder)
    {
        $id = '';
        $customer = $parentOrder->getDetail()->getCustomer();
        if ($customer && $customer->getId()
        ) {
            $id = str_pad((string)$customer->getId(), 8, '0', STR_PAD_LEFT);
        }
        return $id;
    }
}
